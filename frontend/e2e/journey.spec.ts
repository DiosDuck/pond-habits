import { expect, test, type Page } from '@playwright/test'

import { loginAs } from './auth.ts'
import { mailLink } from './mailpit.ts'

// The Phase 3 exit journey on the full stack: invite, register (the invite verifies the email,
// ADR 0025), log out, log in, log out, password reset, log in with the new password.
// A new address for each run and each browser project, so parallel runs do not meet.

async function logOut(page: Page): Promise<void> {
  await page.goto('/more')
  await page.getByRole('button', { name: 'Log out' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Log in' }),
  ).toBeVisible()
}

async function logIn(
  page: Page,
  email: string,
  password: string,
): Promise<void> {
  await page.goto('/login')
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Password').fill(password)
  await page.getByRole('button', { name: 'Log in' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Today' }),
  ).toBeVisible()
}

test('invite, register, log out, log in, reset the password, log in again', async ({
  page,
  request,
}, testInfo) => {
  const email = `journey-${String(Date.now())}-${testInfo.project.name}@example.com`
  const firstPassword = `pond journey ${String(Date.now())} first`
  const newPassword = `pond journey ${String(Date.now())} second`

  // 1. The admin sends an invite.
  await loginAs(page)
  await page.goto('/admin')
  await page.getByLabel('Email').fill(email)
  await page.getByRole('button', { name: 'Send invite' }).click()
  await expect(page.getByRole('status')).toHaveText(`Invite sent to ${email}.`)

  // 2. The invitee registers from the link and is logged in.
  const invite = await mailLink(
    request,
    email,
    /\/register\?token=[A-Za-z0-9]+/,
  )
  await page.context().clearCookies()
  await page.goto(invite)
  await page.getByLabel('Name').fill('Journey')
  await page.getByLabel('Password', { exact: true }).fill(firstPassword)
  await page.getByLabel('Repeat the password').fill(firstPassword)
  await page.getByRole('button', { name: 'Create account' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Today' }),
  ).toBeVisible()

  // 3. Log out and log in again.
  await logOut(page)
  await logIn(page, email, firstPassword)
  await logOut(page)

  // 4. Request a reset link from the login page.
  await page.getByRole('link', { name: 'Forgot your password?' }).click()
  // The page loads on demand: the login page (with its own Email field) stays until it is ready.
  await expect(
    page.getByRole('heading', { level: 1, name: 'Forgot your password?' }),
  ).toBeVisible()
  await page.getByLabel('Email').fill(email)
  await page.getByRole('button', { name: 'Send link' }).click()
  await expect(page.getByRole('status')).toContainText(
    'If an account exists for this address, we sent a link.',
  )

  // 5. Set a new password from the mailed link.
  const reset = await mailLink(
    request,
    email,
    /\/set-password\?token=[A-Za-z0-9]+/,
  )
  await page.goto(reset)
  await page.getByLabel('Email').fill(email)
  // exact: true, because "Repeat the new password" also contains "new password".
  await page.getByLabel('New password', { exact: true }).fill(newPassword)
  await page.getByLabel('Repeat the new password').fill(newPassword)
  await page.getByRole('button', { name: 'Set password' }).click()
  await expect(page.getByText('Your password is set.')).toBeVisible()

  // 6. The new password works.
  await logIn(page, email, newPassword)
})
