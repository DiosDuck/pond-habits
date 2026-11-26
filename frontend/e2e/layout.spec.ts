import { expect, test } from '@playwright/test'

import { loginAs } from './auth.ts'
import { expectNoAxeViolations } from './axe.ts'

// App layout (ADR 0024). The projects use phone viewports (Pixel 7, iPhone 15).
// These checks need real CSS, so they are E2E tests and not component tests.

// All pages need a login (docs/product/login.md).
test.beforeEach(async ({ page }) => {
  await loginAs(page)
})

test.describe('phone layout', () => {
  test('Today has no tab bar: the cards are the menu', async ({ page }) => {
    await page.goto('/')

    await expect(
      page.getByRole('heading', { level: 1, name: 'Today' }),
    ).toBeVisible()
    await expect(page.getByRole('navigation', { name: 'Main' })).toBeHidden()
  })

  test('other pages show the tab bar, and a tab opens its page', async ({
    page,
  }) => {
    await page.goto('/tasks')

    const nav = page.getByRole('navigation', { name: 'Main' })
    await expect(nav).toBeVisible()

    await nav.getByRole('link', { name: 'Habits' }).click()
    await expect(page).toHaveURL(/\/habits$/)
    await expect(
      page.getByRole('heading', { level: 1, name: 'Habits' }),
    ).toBeVisible()
  })

  test('a Today card opens its page', async ({ page }) => {
    await page.goto('/')

    await page.getByRole('link', { name: /start focus/i }).click()
    await expect(page).toHaveURL(/\/focus$/)
  })

  test('Today and a tab page have no accessibility violations', async ({
    page,
  }) => {
    await page.goto('/')
    await expect(
      page.getByRole('heading', { level: 1, name: 'Today' }),
    ).toBeVisible()
    await expectNoAxeViolations(page)

    await page.goto('/tasks')
    await expect(
      page.getByRole('heading', { level: 1, name: 'Tasks' }),
    ).toBeVisible()
    await expectNoAxeViolations(page)
  })
})

test.describe('wide screen layout (1024 px and wider)', () => {
  test.use({ viewport: { width: 1280, height: 800 } })

  test('Today shows the sidebar', async ({ page }) => {
    await page.goto('/')

    await expect(page.getByRole('navigation', { name: 'Main' })).toBeVisible()
  })
})
