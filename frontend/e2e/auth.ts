import { expect, type Page } from '@playwright/test'

// Demo users from the local seed (docs/product/accounts.md). "make e2e" seeds them.
export const demoAdmin = { email: 'admin@demo', password: 'password' }
export const demoUser = { email: 'new@demo', password: 'password' }

// Log in through the login page, like a user.
export async function loginAs(
  page: Page,
  user: { email: string; password: string } = demoAdmin,
): Promise<void> {
  await page.goto('/login')
  await page.getByLabel('Email').fill(user.email)
  await page.getByLabel('Password').fill(user.password)
  await page.getByRole('button', { name: 'Log in' }).click()
  await expect(
    page.getByRole('heading', { level: 1, name: 'Today' }),
  ).toBeVisible()
}
