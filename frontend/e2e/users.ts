import { expect, type APIRequestContext, type Page } from '@playwright/test'

import { loginAs } from './auth.ts'
import { mailLink } from './mailpit.ts'

export interface FreshUser {
  email: string
  name: string
  password: string
}

// A new user for one test: the demo admin invites, the new user registers from the mailed link.
// At the end the page is logged in as the new user, on Today.
export async function registerFreshUser(
  page: Page,
  request: APIRequestContext,
  prefix: string,
  project: string,
): Promise<FreshUser> {
  const stamp = `${String(Date.now())}-${project}`
  const user: FreshUser = {
    email: `${prefix}-${stamp}@example.com`,
    name: `${prefix} ${stamp}`,
    password: `pond ${prefix} ${stamp} password`,
  }

  await loginAs(page)
  await page.goto('/admin')
  await page.getByLabel('Email').fill(user.email)
  await page.getByRole('button', { name: 'Send invite' }).click()
  await expect(page.getByRole('status')).toHaveText(
    `Invite sent to ${user.email}.`,
  )

  const link = await mailLink(
    request,
    user.email,
    /\/register\?token=[A-Za-z0-9]+/,
  )
  await page.context().clearCookies()
  await page.goto(link)
  await page.getByLabel('Name').fill(user.name)
  await page.getByLabel('Password', { exact: true }).fill(user.password)
  await page.getByLabel('Repeat the password').fill(user.password)
  await page.getByRole('button', { name: 'Create account' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Today' }),
  ).toBeVisible()
  return user
}

// Log in through the login page and wait for Today.
export async function logInWith(
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
