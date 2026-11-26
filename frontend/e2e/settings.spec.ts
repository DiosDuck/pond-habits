import { expect, test } from '@playwright/test'

import { logInWith, registerFreshUser } from './users.ts'

// The Settings page on the full stack (docs/product/settings.md). Each test uses a new user.

async function openSettings(
  page: import('@playwright/test').Page,
): Promise<void> {
  await page.goto('/settings')
  await expect(
    page.getByRole('heading', { level: 1, name: 'Settings' }),
  ).toBeVisible()
}

test('the theme changes at once and stays after a reload; the data downloads', async ({
  page,
  request,
}, testInfo) => {
  await registerFreshUser(page, request, 'theme', testInfo.project.name)
  await openSettings(page)

  await page.getByRole('radio', { name: 'Dark' }).click()
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark')
  await page.reload()
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark')

  const download = page.waitForEvent('download')
  await page.getByRole('button', { name: 'Download my data' }).click()
  expect((await download).suggestedFilename()).toMatch(
    /^pond-habits-export-\d{4}-\d{2}-\d{2}\.json$/,
  )
})

test('a new password logs the user out; the new password works', async ({
  page,
  request,
}, testInfo) => {
  const user = await registerFreshUser(
    page,
    request,
    'pw',
    testInfo.project.name,
  )
  const newPassword = `${user.password} new`
  await openSettings(page)

  await page.getByLabel('Current password').fill(user.password)
  await page.getByLabel('New password', { exact: true }).fill(newPassword)
  await page.getByLabel('Repeat the new password').fill(newPassword)
  await page.getByRole('button', { name: 'Change password' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Log in' }),
  ).toBeVisible()

  await logInWith(page, user.email, newPassword)
})

test('a deleted account cannot log in', async ({ page, request }, testInfo) => {
  const user = await registerFreshUser(
    page,
    request,
    'del',
    testInfo.project.name,
  )
  await openSettings(page)

  await page.getByRole('button', { name: 'Delete my account' }).click()
  const dialog = page.getByRole('dialog', { name: 'Delete your account?' })
  await dialog.getByLabel('Password').fill(user.password)
  await dialog.getByRole('button', { name: 'Delete my account' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Log in' }),
  ).toBeVisible()

  await page.getByLabel('Email').fill(user.email)
  await page.getByLabel('Password').fill(user.password)
  await page.getByRole('button', { name: 'Log in' }).click()
  await expect(page.getByRole('alert')).toHaveText(
    'The email or password is not correct.',
  )
})
