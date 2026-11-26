import { expect, test } from '@playwright/test'

import { demoAdmin, demoUser, loginAs } from './auth.ts'
import { expectNoAxeViolations } from './axe.ts'

// Login and logout on the full stack (docs/product/login.md). Demo users: "make e2e" seeds them.

test('a page that needs a login opens the login page, then the page after login', async ({
  page,
}) => {
  await page.goto('/habits')
  await expect(page).toHaveURL(/\/login/)

  await page.getByLabel('Email').fill(demoUser.email)
  await page.getByLabel('Password').fill(demoUser.password)
  await page.getByRole('button', { name: 'Log in' }).click()

  await expect(
    page.getByRole('heading', { level: 1, name: 'Habits' }),
  ).toBeVisible()
})

test('a wrong password shows the general message', async ({ page }) => {
  await page.goto('/login')
  await page.getByLabel('Email').fill(demoUser.email)
  await page.getByLabel('Password').fill('not the password')
  await page.getByRole('button', { name: 'Log in' }).click()

  await expect(page.getByRole('alert')).toHaveText(
    'The email or password is not correct.',
  )
})

test('an admin sees the Admin link, and log out ends the session', async ({
  page,
}) => {
  await loginAs(page, demoAdmin)
  await expect(page.getByRole('link', { name: 'Admin' })).toBeVisible()

  await page.goto('/more')
  await page.getByRole('button', { name: 'Log out' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Log in' }),
  ).toBeVisible()

  await page.goto('/tasks')
  await expect(page).toHaveURL(/\/login/)
})

test('the login and set-password pages have no accessibility violations', async ({
  page,
}) => {
  await page.goto('/login')
  await expect(
    page.getByRole('heading', { level: 1, name: 'Log in' }),
  ).toBeVisible()
  await expectNoAxeViolations(page)

  await page.goto('/set-password?token=example')
  await expect(
    page.getByRole('heading', { level: 1, name: 'Set your password' }),
  ).toBeVisible()
  await expectNoAxeViolations(page)
})
