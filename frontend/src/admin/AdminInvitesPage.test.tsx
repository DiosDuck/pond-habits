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

// Admin pending invites: cancel (docs/product/admin.md).

interface ApiInvite {
  id: string
  email: string
  expires_at: string
}

function invitesServer(initial: ApiInvite[]): { deleted: string[] } {
  let invites = initial
  const deleted: string[] = []
  server.use(
    http.get('/api/admin/invites', () => HttpResponse.json(invites)),
    http.delete('/api/admin/invites/:id', ({ params }) => {
      const { id } = params as { id: string }
      deleted.push(id)
      invites = invites.filter((invite) => invite.id !== id)
      return new HttpResponse(null, { status: 204 })
    }),
  )
  return { deleted }
}

const friend: ApiInvite = {
  id: 'i-1',
  email: 'friend@example.com',
  expires_at: '2026-12-03T10:00:00+00:00',
}

describe('AdminInvitesPage', () => {
  it('shows each pending invite with its expiry date', async () => {
    setSession(testAdmin)
    invitesServer([friend])
    renderApp('/admin/invites')

    const row = (await screen.findByText('friend@example.com')).closest(
      'section',
    )
    if (row === null) throw new Error('No row')
    expect(within(row).getByText('Expires Dec 3, 2026')).toBeInTheDocument()
  })

  it('cancels an invite at once', async () => {
    setSession(testAdmin)
    const { deleted } = invitesServer([friend])
    renderApp('/admin/invites')

    fireEvent.click(
      await screen.findByRole('button', { name: 'Cancel invite' }),
    )

    expect(await screen.findByText('No pending invites.')).toBeInTheDocument()
    expect(deleted).toEqual(['i-1'])
  })

  it('says when there is no pending invite', async () => {
    setSession(testAdmin)
    invitesServer([])
    renderApp('/admin/invites')

    expect(await screen.findByText('No pending invites.')).toBeInTheDocument()
  })

  it('shows a note and loads no list for a user who is not an admin', async () => {
    setSession(testUser)
    let requests = 0
    server.use(
      http.get('/api/admin/invites', () => {
        requests += 1
        return HttpResponse.json([])
      }),
    )
    renderApp('/admin/invites')

    expect(
      await screen.findByText('Only admins can open this page.'),
    ).toBeInTheDocument()
    expect(requests).toBe(0)
  })
})
