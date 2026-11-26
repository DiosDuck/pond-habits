import { fireEvent, screen, within } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import {
  renderApp,
  setSession,
  testAdmin,
  testUser,
} from '../test/renderApp.tsx'
import { server } from '../test/server.ts'

// Admin users list: disable and enable (docs/product/admin.md).

interface ApiUser {
  id: string
  name: string
  email: string
  role: 'admin' | 'user'
  disabled: boolean
  joined_at: string
}

const me: ApiUser = {
  id: testAdmin.id,
  name: testAdmin.name,
  email: testAdmin.email,
  role: 'admin',
  disabled: false,
  joined_at: '2026-11-20T10:00:00+00:00',
}
const bo: ApiUser = {
  id: 'u-bo',
  name: 'Bo',
  email: 'bo@example.com',
  role: 'user',
  disabled: false,
  joined_at: '2026-11-24T10:00:00+00:00',
}
const cy: ApiUser = {
  id: 'u-cy',
  name: 'Cy',
  email: 'cy@example.com',
  role: 'user',
  disabled: true,
  joined_at: '2026-11-22T10:00:00+00:00',
}

// A fake server with a users list that the disable and enable calls change.
function usersServer(initial: ApiUser[]): { posts: string[] } {
  let users = initial
  const posts: string[] = []
  server.use(
    http.get('/api/admin/users', () => HttpResponse.json(users)),
    http.post('/api/admin/users/:id/:action', ({ params }) => {
      const { id, action } = params as { id: string; action: string }
      posts.push(`${id}/${action}`)
      users = users.map((u) =>
        u.id === id ? { ...u, disabled: action === 'disable' } : u,
      )
      return HttpResponse.json(users.find((u) => u.id === id))
    }),
  )
  return { posts }
}

async function card(name: string): Promise<HTMLElement> {
  const heading = await screen.findByText(name, { selector: 'h2' })
  const section = heading.closest('section')
  if (section === null) throw new Error(`No card for ${name}`)
  return section
}

describe('AdminUsersPage', () => {
  it('shows each user with chips and the join date', async () => {
    setSession(testAdmin)
    usersServer([me, bo, cy])
    renderApp('/admin/users')

    const own = await card(me.name)
    expect(within(own).getByText('Admin')).toBeInTheDocument()
    expect(within(own).getByText('You')).toBeInTheDocument()
    const boCard = await card('Bo')
    expect(within(boCard).getByText('bo@example.com')).toBeInTheDocument()
    expect(within(boCard).getByText('Joined Nov 24, 2026')).toBeInTheDocument()
    const cyCard = await card('Cy')
    expect(within(cyCard).getByText('Disabled')).toBeInTheDocument()
  })

  it('has no Disable button on the own card', async () => {
    setSession(testAdmin)
    usersServer([me, bo])
    renderApp('/admin/users')

    const own = await card(me.name)
    expect(
      within(own).queryByRole('button', { name: 'Disable' }),
    ).not.toBeInTheDocument()
    expect(
      within(await card('Bo')).getByRole('button', { name: 'Disable' }),
    ).toBeInTheDocument()
  })

  it('disables a user only after the confirm', async () => {
    setSession(testAdmin)
    const { posts } = usersServer([me, bo])
    renderApp('/admin/users')

    fireEvent.click(
      within(await card('Bo')).getByRole('button', { name: 'Disable' }),
    )
    const dialog = await screen.findByRole('dialog', { name: 'Disable Bo?' })
    expect(
      within(dialog).getByText('Bo is logged out on all devices at once.'),
    ).toBeInTheDocument()
    expect(posts).toEqual([])

    fireEvent.click(within(dialog).getByRole('button', { name: 'Disable' }))

    expect(
      await within(await card('Bo')).findByText('Disabled'),
    ).toBeInTheDocument()
    expect(posts).toEqual(['u-bo/disable'])
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('closes the confirm with Cancel and does nothing', async () => {
    setSession(testAdmin)
    const { posts } = usersServer([me, bo])
    renderApp('/admin/users')

    fireEvent.click(
      within(await card('Bo')).getByRole('button', { name: 'Disable' }),
    )
    const dialog = await screen.findByRole('dialog', { name: 'Disable Bo?' })
    fireEvent.click(within(dialog).getByRole('button', { name: 'Cancel' }))

    await screen.findByText('Bo', { selector: 'h2' })
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    expect(posts).toEqual([])
  })

  it('enables a disabled user at once', async () => {
    setSession(testAdmin)
    const { posts } = usersServer([me, cy])
    renderApp('/admin/users')

    fireEvent.click(
      within(await card('Cy')).getByRole('button', { name: 'Enable' }),
    )

    expect(
      await within(await card('Cy')).findByRole('button', { name: 'Disable' }),
    ).toBeInTheDocument()
    expect(posts).toEqual(['u-cy/enable'])
  })

  it('shows the server message when the server refuses', async () => {
    setSession(testAdmin)
    usersServer([me, bo])
    server.use(
      http.post('/api/admin/users/:id/disable', () =>
        HttpResponse.json(
          {
            message: 'You cannot disable your own account.',
            errors: { user: ['You cannot disable your own account.'] },
          },
          { status: 422 },
        ),
      ),
    )
    renderApp('/admin/users')

    fireEvent.click(
      within(await card('Bo')).getByRole('button', { name: 'Disable' }),
    )
    const dialog = await screen.findByRole('dialog', { name: 'Disable Bo?' })
    fireEvent.click(within(dialog).getByRole('button', { name: 'Disable' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'You cannot disable your own account.',
    )
  })

  it('links back to the Admin page', async () => {
    setSession(testAdmin)
    usersServer([me])
    renderApp('/admin/users')

    expect(await screen.findByRole('link', { name: 'Admin' })).toHaveAttribute(
      'href',
      '/admin',
    )
  })

  it('shows a note and loads no list for a user who is not an admin', async () => {
    setSession(testUser)
    let requests = 0
    server.use(
      http.get('/api/admin/users', () => {
        requests += 1
        return HttpResponse.json([])
      }),
    )
    renderApp('/admin/users')

    expect(
      await screen.findByText('Only admins can open this page.'),
    ).toBeInTheDocument()
    expect(requests).toBe(0)
  })
})
