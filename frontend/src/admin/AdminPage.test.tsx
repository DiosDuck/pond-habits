import { fireEvent, screen } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import {
  renderApp,
  setSession,
  testAdmin,
  testUser,
} from '../test/renderApp.tsx'
import { server } from '../test/server.ts'

// Admin page: send an invite (docs/product/invites.md).
// The list of invites and users comes in a later PR. These tests check only the invite form.

function sendInvite(email: string): void {
  fireEvent.change(screen.getByLabelText('Email'), { target: { value: email } })
  fireEvent.click(screen.getByRole('button', { name: 'Send invite' }))
}

describe('AdminPage invite form', () => {
  it('sends an invite, confirms it and clears the field', async () => {
    setSession(testAdmin)
    let sent: unknown = null
    server.use(
      http.post('/api/admin/invites', async ({ request }) => {
        sent = await request.json()
        return HttpResponse.json(
          {
            id: 'i-1',
            email: 'friend@example.com',
            expires_at: '2026-12-03T10:00:00Z',
          },
          { status: 201 },
        )
      }),
    )
    renderApp('/admin')
    await screen.findByRole('heading', { level: 1, name: 'Admin' })

    sendInvite('friend@example.com')

    expect(await screen.findByRole('status')).toHaveTextContent(
      'Invite sent to friend@example.com.',
    )
    expect(sent).toEqual({ email: 'friend@example.com' })
    expect(screen.getByLabelText('Email')).toHaveValue('')
  })

  it('shows the server message for the email field', async () => {
    setSession(testAdmin)
    server.use(
      http.post('/api/admin/invites', () =>
        HttpResponse.json(
          {
            message: 'This email address has an account already.',
            errors: { email: ['This email address has an account already.'] },
          },
          { status: 422 },
        ),
      ),
    )
    renderApp('/admin')
    await screen.findByRole('heading', { level: 1, name: 'Admin' })

    sendInvite('new@demo')

    expect(
      await screen.findByText('This email address has an account already.'),
    ).toBeInTheDocument()
  })

  it('asks the admin to wait after too many invites', async () => {
    setSession(testAdmin)
    server.use(
      http.post('/api/admin/invites', () =>
        HttpResponse.json({ message: 'Too Many Attempts.' }, { status: 429 }),
      ),
    )
    renderApp('/admin')
    await screen.findByRole('heading', { level: 1, name: 'Admin' })

    sendInvite('friend@example.com')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Too many invites. Try again later.',
    )
  })

  it('asks for an email before it sends the form', async () => {
    setSession(testAdmin)
    let calls = 0
    server.use(
      http.post('/api/admin/invites', () => {
        calls += 1
        return HttpResponse.json({}, { status: 201 })
      }),
    )
    renderApp('/admin')
    await screen.findByRole('heading', { level: 1, name: 'Admin' })

    sendInvite('')

    expect(await screen.findByText('Enter your email.')).toBeInTheDocument()
    expect(calls).toBe(0)
  })

  it('shows a note and no form to a user who is not an admin', async () => {
    setSession(testUser)
    renderApp('/admin')

    expect(
      await screen.findByText('Only admins can open this page.'),
    ).toBeInTheDocument()
    expect(
      screen.queryByRole('button', { name: 'Send invite' }),
    ).not.toBeInTheDocument()
  })
})
