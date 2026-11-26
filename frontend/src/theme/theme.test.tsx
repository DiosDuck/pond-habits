import { screen } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { renderApp, testUser } from '../test/renderApp.tsx'
import { server } from '../test/server.ts'
import { applyTheme, startTheme } from './theme.ts'

// The theme (docs/product/settings.md): "system" follows the device; "light" and "dark" set
// data-theme on <html>. The device keeps a copy, so the page has the right colours at start.

afterEach(() => {
  document.documentElement.removeAttribute('data-theme')
  localStorage.clear()
})

describe('applyTheme', () => {
  it('sets data-theme for light and dark and keeps a copy', () => {
    applyTheme('dark')
    expect(document.documentElement.dataset['theme']).toBe('dark')
    expect(localStorage.getItem('theme')).toBe('dark')

    applyTheme('light')
    expect(document.documentElement.dataset['theme']).toBe('light')
  })

  it('removes data-theme for system', () => {
    applyTheme('dark')
    applyTheme('system')

    expect(document.documentElement.hasAttribute('data-theme')).toBe(false)
    expect(localStorage.getItem('theme')).toBe('system')
  })
})

describe('startTheme', () => {
  it('uses the copy on the device at start', () => {
    localStorage.setItem('theme', 'dark')

    startTheme()

    expect(document.documentElement.dataset['theme']).toBe('dark')
  })

  it('ignores a value that is not a theme', () => {
    localStorage.setItem('theme', 'purple')

    startTheme()

    expect(document.documentElement.hasAttribute('data-theme')).toBe(false)
  })
})

describe('theme of the account', () => {
  it('uses the theme from /api/me after login', async () => {
    server.use(
      http.get('/api/me', () =>
        HttpResponse.json({ ...testUser, theme: 'dark' }),
      ),
    )
    renderApp('/')

    await screen.findByRole('heading', { level: 1, name: 'Today' })
    await vi.waitFor(() => {
      expect(document.documentElement.dataset['theme']).toBe('dark')
    })
  })
})
