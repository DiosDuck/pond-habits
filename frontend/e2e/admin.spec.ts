import { expect, test, type Page } from '@playwright/test'

import { loginAs } from './auth.ts'
import { mailLink } from './mailpit.ts'

// Admin pages on the full stack (docs/product/admin.md).
// Each test makes its own new user or invite, so parallel runs and the demo users stay untouched.

async function sendInvite(page: Page, email: string): Promise<void> {
  await page.goto('/admin')
  await page.getByLabel('Email').fill(email)
  await page.getByRole('button', { name: 'Send invite' }).click()
  await expect(page.getByRole('status')).toHaveText(`Invite sent to ${email}.`)
}

test('an admin disables a user, who then cannot log in', async ({
  page,
  request,
}, testInfo) => {
  const stamp = `${String(Date.now())}-${testInfo.project.name}`
  const email = `disable-${stamp}@example.com`
  const name = `Person ${stamp}`
  const password = `pond admin ${stamp} password`

  await loginAs(page)
  await sendInvite(page, email)
  const link = await mailLink(request, email, /\/register\?token=[A-Za-z0-9]+/)
  await page.context().clearCookies()
  await page.goto(link)
  await page.getByLabel('Name').fill(name)
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByLabel('Repeat the password').fill(password)
  await page.getByRole('button', { name: 'Create account' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Today' }),
  ).toBeVisible()

  // The admin disables the new user.
  await page.context().clearCookies()
  await loginAs(page)
  await page.goto('/admin/users')
  const card = page.locator('section').filter({
    has: page.getByRole('heading', { name, exact: true }),
  })
  await card.getByRole('button', { name: 'Disable' }).click()
  const dialog = page.getByRole('dialog', { name: `Disable ${name}?` })
  await dialog.getByRole('button', { name: 'Disable' }).click()
  await expect(card.getByText('Disabled', { exact: true })).toBeVisible()

  // The disabled user gets the general login error.
  await page.context().clearCookies()
  await page.goto('/login')
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Password').fill(password)
  await page.getByRole('button', { name: 'Log in' }).click()
  await expect(page.getByRole('alert')).toHaveText(
    'The email or password is not correct.',
  )
})

test('an admin cancels a pending invite', async ({ page }, testInfo) => {
  const email = `cancel-${String(Date.now())}-${testInfo.project.name}@example.com`

  await loginAs(page)
  await sendInvite(page, email)
  await page.goto('/admin/invites')
  const row = page.locator('section').filter({ hasText: email })
  await row.getByRole('button', { name: 'Cancel invite' }).click()

  await expect(page.getByText(email)).toHaveCount(0)
})
