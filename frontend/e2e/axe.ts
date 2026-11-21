import AxeBuilder from '@axe-core/playwright'
import { expect, type Page } from '@playwright/test'

// WCAG 2.2 level AA (ADR 0009, rules.md: "no axe violations").
const wcagTags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']

export async function expectNoAxeViolations(page: Page): Promise<void> {
  const results = await new AxeBuilder({ page }).withTags(wcagTags).analyze()
  expect(results.violations).toEqual([])
}
