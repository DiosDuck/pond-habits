import { fireEvent, screen } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import { renderApp, setSession } from '../test/renderApp.tsx'
import { server } from '../test/server.ts'

// Set the password from the mailed link (docs/product/login.md). No login is needed.

function fillForm(email: string, password: string, repeat = password): void {
  fireEvent.change(screen.getByLabelText('Email'), {
    target: { value: email },
  })
  fireEvent.change(screen.getByLabelText('New password'), {
    target: { value: password },
  })
  fireEvent.change(screen.getByLabelText('Repeat the new password'), {
    target: { value: repeat },
  })
  fireEvent.click(screen.getByRole('button', { name: 'Set password' }))
}

describe('SetPasswordPage', () => {
  it('sets the password and links to the login page', async () => {
    setSession(null)
    let sent: unknown = null
    server.use(
      http.post('/api/password/set', async ({ request }) => {
        sent = await request.json()
        return new HttpResponse(null, { status: 204 })
      }),
    )
    renderApp('/set-password?token=abc123')
    await screen.findByRole('heading', { level: 1, name: 'Set your password' })

    fillForm('admin@demo', 'correct horse battery staple')

    expect(await screen.findByText('Your password is set.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Log in' })).toHaveAttribute(
      'href',
      '/login',
    )
    expect(sent).toEqual({
      token: 'abc123',
      email: 'admin@demo',
      password: 'correct horse battery staple',
      password_confirmation: 'correct horse battery staple',
    })
  })

  it('says that a link works once and expires', async () => {
    setSession(null)
    renderApp('/set-password?token=abc123')

    expect(
      await screen.findByText(/The link works once and expires/),
    ).toBeInTheDocument()
  })

  it('checks that the two passwords are the same', async () => {
    setSession(null)
    renderApp('/set-password?token=abc123')
    await screen.findByRole('heading', { level: 1, name: 'Set your password' })

    fillForm(
      'admin@demo',
      'correct horse battery staple',
      'something else here',
    )

    expect(
      await screen.findByText('The passwords do not match.'),
    ).toBeInTheDocument()
  })

  it('asks for 12 characters or more', async () => {
    setSession(null)
    renderApp('/set-password?token=abc123')
    await screen.findByRole('heading', { level: 1, name: 'Set your password' })

    fillForm('admin@demo', 'short pass')

    expect(
      await screen.findByText('Use 12 characters or more.'),
    ).toBeInTheDocument()
  })

  it('shows "link not valid" when the server rejects the token', async () => {
    setSession(null)
    server.use(
      http.post('/api/password/set', () =>
        HttpResponse.json(
          {
            message: 'This link is not valid or has expired.',
            errors: { token: ['This link is not valid or has expired.'] },
          },
          { status: 422 },
        ),
      ),
    )
    renderApp('/set-password?token=used')
    await screen.findByRole('heading', { level: 1, name: 'Set your password' })

    fillForm('admin@demo', 'correct horse battery staple')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'This link is not valid or has expired.',
    )
  })

  it('shows the server message for a password that the server rejects', async () => {
    setSession(null)
    server.use(
      http.post('/api/password/set', () =>
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
    renderApp('/set-password?token=abc123')
    await screen.findByRole('heading', { level: 1, name: 'Set your password' })

    fillForm('admin@demo', 'a long but breached password')

    expect(
      await screen.findByText(
        'The given password has appeared in a data leak.',
      ),
    ).toBeInTheDocument()
  })

  it('shows "link not valid" when the link has no token', async () => {
    setSession(null)
    renderApp('/set-password')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'This link is not valid or has expired.',
    )
    expect(
      screen.queryByRole('button', { name: 'Set password' }),
    ).not.toBeInTheDocument()
  })
})
