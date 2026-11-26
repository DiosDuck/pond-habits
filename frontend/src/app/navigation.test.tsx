import { fireEvent, screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'

import { renderAt } from '../test/render.tsx'

// App layout and navigation (ADR 0024).
// Visibility by screen width (tab bar, sidebar) is CSS. The E2E test e2e/layout.spec.ts checks it.

describe('Today page', () => {
  it('is the page at /', () => {
    renderAt('/')

    expect(
      screen.getByRole('heading', { level: 1, name: 'Today' }),
    ).toBeInTheDocument()
  })

  // The Admin link shows for admins only: src/auth/auth.test.tsx checks it.
  it('has a Settings link with a visible label in the header', () => {
    renderAt('/')

    const header = screen.getByRole('banner')
    expect(
      within(header).getByRole('link', { name: 'Settings' }),
    ).toHaveAttribute('href', '/settings')
  })

  it.each([
    [/credits/i, '/rewards'],
    [/start focus/i, '/focus'],
    [/tasks/i, '/tasks'],
    [/habits/i, '/habits'],
    [/nutrition/i, '/nutrition'],
  ])('has a card %s that links to %s', (name, href) => {
    renderAt('/')

    const main = screen.getByRole('main')
    expect(within(main).getByRole('link', { name })).toHaveAttribute(
      'href',
      href,
    )
  })
})

describe('main navigation', () => {
  it('has the five tabs in order', () => {
    renderAt('/tasks')

    const nav = screen.getByRole('navigation', { name: 'Main' })
    const names = within(nav)
      .getAllByRole('link')
      .map((link) => link.textContent)
    expect(names).toEqual(['Today', 'Focus', 'Tasks', 'Habits', 'More'])
  })

  it('marks the tab of the current page', () => {
    renderAt('/tasks')

    const nav = screen.getByRole('navigation', { name: 'Main' })
    expect(within(nav).getByRole('link', { name: 'Tasks' })).toHaveAttribute(
      'aria-current',
      'page',
    )
    expect(
      within(nav).getByRole('link', { name: 'Habits' }),
    ).not.toHaveAttribute('aria-current')
  })

  it('opens the page of a tab', async () => {
    renderAt('/tasks')

    const nav = screen.getByRole('navigation', { name: 'Main' })
    fireEvent.click(within(nav).getByRole('link', { name: 'Habits' }))

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Habits' }),
    ).toBeInTheDocument()
  })
})

describe('pages that come in a later version', () => {
  it.each([
    ['/focus', 'Focus'],
    ['/tasks', 'Tasks'],
    ['/habits', 'Habits'],
    ['/nutrition', 'Nutrition'],
    ['/rewards', 'Rewards'],
    ['/settings', 'Settings'],
    ['/admin', 'Admin'],
  ])('%s shows the page name and a "later version" note', (path, title) => {
    renderAt(path)

    expect(
      screen.getByRole('heading', { level: 1, name: title }),
    ).toBeInTheDocument()
    expect(
      screen.getByText('This page comes in a later version.'),
    ).toBeInTheDocument()
  })
})

describe('More page', () => {
  it('links to the pages that are not in the tab bar', () => {
    renderAt('/more')

    const main = screen.getByRole('main')
    expect(
      screen.getByRole('heading', { level: 1, name: 'More' }),
    ).toBeInTheDocument()
    const links = [
      ['Nutrition', '/nutrition'],
      ['Rewards', '/rewards'],
      ['Settings', '/settings'],
    ] as const
    for (const [name, href] of links) {
      expect(within(main).getByRole('link', { name })).toHaveAttribute(
        'href',
        href,
      )
    }
  })
})

describe('unknown path', () => {
  it('shows "Page not found" with a link to Today', () => {
    renderAt('/no-such-page')

    expect(
      screen.getByRole('heading', { level: 1, name: 'Page not found' }),
    ).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Go to Today' })).toHaveAttribute(
      'href',
      '/',
    )
  })
})
