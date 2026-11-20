import { expect, test } from '@playwright/test'

import { expectNoAxeViolations } from './axe.ts'

test.describe('start page', () => {
  test('shows the app name and a ready server (full stack)', async ({
    page,
  }) => {
    await page.goto('/')

    await expect(
      page.getByRole('heading', { name: 'Pond Habits' }),
    ).toBeVisible()
    await expect(page.getByRole('status')).toHaveText('The server is ready.')
  })

  test('has no accessibility violations', async ({ page }) => {
    await page.goto('/')
    await expect(page.getByRole('status')).toHaveText('The server is ready.')

    await expectNoAxeViolations(page)
  })
})
