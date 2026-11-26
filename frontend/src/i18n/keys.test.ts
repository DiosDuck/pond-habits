import { describe, expect, it } from 'vitest'

import i18n from 'i18next'

import en from './en.json'
import './i18n.ts'

// All UI texts come from i18n keys in en.json (ADR 0001).
// Rule: the code writes each key in full, in quotes, for example t('nav.today').
// A key that is built at run time (`pages.${page}`) cannot be found, so this test refuses it.

// i18next plural forms: t('items', { count }) reads "items_one" or "items_other".
const pluralSuffix = /_(?:zero|one|two|few|many|other)$/

function leaves(tree: object, prefix = ''): [string, unknown][] {
  return Object.entries(tree as Record<string, unknown>).flatMap(
    ([name, value]): [string, unknown][] =>
      typeof value === 'object' && value !== null
        ? leaves(value, `${prefix}${name}.`)
        : [[`${prefix}${name}`, value]],
  )
}

// The source code of the app (not the tests), read as text by Vite.
const appFiles = import.meta.glob<string>(
  ['../**/*.{ts,tsx}', '!../**/*.test.{ts,tsx}', '!../test/**'],
  { query: '?raw', import: 'default', eager: true },
)
const appCode = Object.values(appFiles).join('\n')

describe('i18n keys', () => {
  it('has a non-empty English text for each key', () => {
    const empty = leaves(en)
      .filter(([, text]) => typeof text !== 'string' || text.trim() === '')
      .map(([key]) => key)

    expect(leaves(en).length).toBeGreaterThan(0)
    expect(empty).toEqual([])
  })

  it('uses each key of en.json in the app code', () => {
    const unused = leaves(en)
      .map(([key]) => key.replace(pluralSuffix, ''))
      .filter(
        (key) =>
          !appCode.includes(`'${key}'`) &&
          !appCode.includes(`"${key}"`) &&
          !appCode.includes(`\`${key}\``),
      )

    expect(unused).toEqual([])
  })

  it('gives the English text for a key', () => {
    expect(i18n.t('app.name')).toBe('Pond Habits')
  })

  it('refuses a key that is not in en.json at compile time', () => {
    // @ts-expect-error -- The key is not in en.json, so TypeScript must refuse it (make lint fails if it does not).
    expect(i18n.t('no.such.key')).toBe('no.such.key')
  })
})
