import { fireEvent, screen } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import { renderApp, setSession, testUser } from '../test/renderApp.tsx'
import { server } from '../test/server.ts'

// Register from the invite link (docs/product/invites.md). No login is needed.

function validInvite(): void {
  setSession(null)
  server.use(
    http.post('/api/invites/check', () =>
      HttpResponse.json({ email: 'new@demo' }),
    ),
  )
}

function fillForm(name: string, password: string, repeat = password): void {
  fireEvent.change(screen.getByLabelText('Name'), { target: { value: name } })
  fireEvent.change(screen.getByLabelText('Password'), {
    target: { value: password },
  })
  fireEvent.change(screen.getByLabelText('Repeat the password'), {
    target: { value: repeat },
  })
  fireEvent.click(screen.getByRole('button', { name: 'Create account' }))
}

describe('RegisterPage', () => {
  it('shows the invited address, read-only', async () => {
    validInvite()
    renderApp('/register?token=abc123')

    expect(
      await screen.findByRole('heading', {
        level: 1,
        name: 'Create your account',
      }),
    ).toBeInTheDocument()
    expect(await screen.findByText('new@demo')).toBeInTheDocument()
    expect(screen.queryByLabelText('Email')).not.toBeInTheDocument()
  })

  it('creates the account, logs in and opens Today', async () => {
    validInvite()
    let sent: unknown = null
    server.use(
      http.post('/api/register', async ({ request }) => {
        sent = await request.json()
        setSession(testUser)
        return HttpResponse.json(testUser, { status: 201 })
      }),
    )
    renderApp('/register?token=abc123')
    await screen.findByText('new@demo')

    fillForm('Bo', 'correct horse battery staple')

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Today' }),
    ).toBeInTheDocument()
    expect(sent).toEqual({
      token: 'abc123',
      name: 'Bo',
      password: 'correct horse battery staple',
      password_confirmation: 'correct horse battery staple',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    })
  })

  it('shows "invite not valid" and no form when the invite check fails', async () => {
    setSession(null)
    server.use(
      http.post('/api/invites/check', () =>
        HttpResponse.json(
          {
            message: 'This invite is not valid or has expired.',
            errors: { token: ['This invite is not valid or has expired.'] },
          },
          { status: 422 },
        ),
      ),
    )
    renderApp('/register?token=used')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'This invite is not valid or has expired.',
    )
    expect(
      screen.queryByRole('button', { name: 'Create account' }),
    ).not.toBeInTheDocument()
  })

  it('shows "invite not valid" when the link has no token', async () => {
    setSession(null)
    renderApp('/register')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'This invite is not valid or has expired.',
    )
    expect(
      screen.queryByRole('button', { name: 'Create account' }),
    ).not.toBeInTheDocument()
  })

  it('checks the fields before it sends the form', async () => {
    validInvite()
    let calls = 0
    server.use(
      http.post('/api/register', () => {
        calls += 1
        return HttpResponse.json(testUser, { status: 201 })
      }),
    )
    renderApp('/register?token=abc123')
    await screen.findByText('new@demo')

    fillForm('', 'short pass', 'other text')

    expect(await screen.findByText('Enter your name.')).toBeInTheDocument()
    expect(screen.getByText('Use 12 characters or more.')).toBeInTheDocument()
    expect(screen.getByText('The passwords do not match.')).toBeInTheDocument()
    expect(calls).toBe(0)
  })

  it('shows the server message for a password that the server rejects', async () => {
    validInvite()
    server.use(
      http.post('/api/register', () =>
        HttpResponse.json(
          {
            message: 'The given password has appeared in a data leak.',
            errors: {
              password: ['The given password has appeared in a data leak.'],
            },
          },
          { status: 422 },
        ),
      ),
    )
    renderApp('/register?token=abc123')
    await screen.findByText('new@demo')

    fillForm('Bo', 'a long but breached secret')

    expect(
      await screen.findByText(
        'The given password has appeared in a data leak.',
      ),
    ).toBeInTheDocument()
  })
})
