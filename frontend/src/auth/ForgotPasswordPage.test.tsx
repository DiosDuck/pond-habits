import { fireEvent, screen } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import { renderApp, setSession } from '../test/renderApp.tsx'
import { server } from '../test/server.ts'

// Password reset request (docs/product/password-reset.md). No login is needed.

function requestLink(email: string): void {
  fireEvent.change(screen.getByLabelText('Email'), { target: { value: email } })
  fireEvent.click(screen.getByRole('button', { name: 'Send link' }))
}

describe('ForgotPasswordPage', () => {
  it('is linked from the login page', async () => {
    setSession(null)
    renderApp('/login')

    expect(
      await screen.findByRole('link', { name: 'Forgot your password?' }),
    ).toHaveAttribute('href', '/forgot-password')
  })

  it('sends the address and shows the general answer', async () => {
    setSession(null)
    let sent: unknown = null
    server.use(
      http.post('/api/password/forgot', async ({ request }) => {
        sent = await request.json()
        return new HttpResponse(null, { status: 204 })
      }),
    )
    renderApp('/forgot-password')
    await screen.findByRole('heading', {
      level: 1,
      name: 'Forgot your password?',
    })

    requestLink('new@demo')

    expect(await screen.findByRole('status')).toHaveTextContent(
      'If an account exists for this address, we sent a link. Check your mail.',
    )
    expect(sent).toEqual({ email: 'new@demo' })
  })

  it('asks the user to wait after too many requests', async () => {
    setSession(null)
    server.use(
      http.post('/api/password/forgot', () =>
        HttpResponse.json({ message: 'Too Many Attempts.' }, { status: 429 }),
      ),
    )
    renderApp('/forgot-password')
    await screen.findByRole('heading', {
      level: 1,
      name: 'Forgot your password?',
    })

    requestLink('new@demo')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Too many requests. Try again later.',
    )
  })

  it('asks for an email before it sends the form', async () => {
    setSession(null)
    let calls = 0
    server.use(
      http.post('/api/password/forgot', () => {
        calls += 1
        return new HttpResponse(null, { status: 204 })
      }),
    )
    renderApp('/forgot-password')
    await screen.findByRole('heading', {
      level: 1,
      name: 'Forgot your password?',
    })

    requestLink('')

    expect(await screen.findByText('Enter your email.')).toBeInTheDocument()
    expect(calls).toBe(0)
  })

  it('links back to the login page', async () => {
    setSession(null)
    renderApp('/forgot-password')

    expect(
      await screen.findByRole('link', { name: 'Back to log in' }),
    ).toHaveAttribute('href', '/login')
  })
})
