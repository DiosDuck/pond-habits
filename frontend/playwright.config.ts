import { defineConfig, devices } from '@playwright/test'

// End-to-end tests (ADR 0008): the full Docker stack, mobile viewports, axe accessibility checks.
// Run them with "make e2e". They run in the official Playwright image (the "e2e" service).
const isCi = Boolean(process.env['CI'])

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: isCi,
  retries: isCi ? 1 : 0,
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: 'playwright-report' }],
  ],
  use: {
    baseURL: process.env['BASE_URL'] ?? 'http://localhost:8080',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'android-chrome', use: { ...devices['Pixel 7'] } },
    { name: 'iphone-safari', use: { ...devices['iPhone 15'] } },
  ],
})
