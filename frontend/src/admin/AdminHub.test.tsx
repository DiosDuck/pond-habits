import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'

import {
  renderApp,
  setSession,
  testAdmin,
  testUser,
} from '../test/renderApp.tsx'

// The Admin page is a hub: the invite form and links to the lists (docs/product/admin.md).
// It loads no lists, so it makes no GET request.

describe('Admin hub links', () => {
  it('links to the Users and Invites pages for an admin', async () => {
    setSession(testAdmin)
    renderApp('/admin')

    expect(await screen.findByRole('link', { name: /Users/ })).toHaveAttribute(
      'href',
      '/admin/users',
    )
    expect(screen.getByRole('link', { name: /Invites/ })).toHaveAttribute(
      'href',
      '/admin/invites',
    )
  })

  it('shows no links to a user who is not an admin', async () => {
    setSession(testUser)
    renderApp('/admin')

    await screen.findByText('Only admins can open this page.')
    expect(
      screen.queryByRole('link', { name: /Users/ }),
    ).not.toBeInTheDocument()
  })
})
