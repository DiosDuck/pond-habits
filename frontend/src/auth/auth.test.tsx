import { fireEvent, screen, within } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import {
  renderApp,
  testAdmin,
  testUser,
  setSession,
} from '../test/renderApp.tsx'
import { server } from '../test/server.ts'

// Login, logout and the login check (docs/product/login.md, ADR 0005).

function fillLogin(email: string, password: string): void {
  fireEvent.change(screen.getByLabelText('Email'), {
    target: { value: email },
  })
  fireEvent.change(screen.getByLabelText('Password'), {
    target: { value: password },
  })
  fireEvent.click(screen.getByRole('button', { name: 'Log in' }))
}

describe('login check', () => {
  it('opens the login page for a page that needs a login', async () => {
    setSession(null)
    const { router } = renderApp('/tasks')

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Log in' }),
    ).toBeInTheDocument()
    expect(router.state.location.pathname).toBe('/login')
  })

  it('opens the page for a logged-in user', async () => {
    setSession(testUser)
    renderApp('/tasks')

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Tasks' }),
    ).toBeInTheDocument()
  })

  it('sends a logged-in user from /login to Today', async () => {
    setSession(testUser)
    renderApp('/login')

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Today' }),
    ).toBeInTheDocument()
  })
})

describe('login page', () => {
  it('logs in and returns to the page that the user asked for', async () => {
    setSession(null)
    let sent: unknown = null
    server.use(
      http.post('/api/login', async ({ request }) => {
        sent = await request.json()
        setSession(testUser)
        return HttpResponse.json(testUser)
      }),
    )
    renderApp('/tasks')
    await screen.findByRole('heading', { level: 1, name: 'Log in' })

    fillLogin('new@demo', 'a good long password')

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Tasks' }),
    ).toBeInTheDocument()
    expect(sent).toEqual({
      email: 'new@demo',
      password: 'a good long password',
    })
  })

  it('goes to Today when the return path is not a page of this app', async () => {
    setSession(null)
    server.use(
      http.post('/api/login', () => {
        setSession(testUser)
        return HttpResponse.json(testUser)
      }),
    )
    renderApp('/login?next=%2F%2Fevil.example')
    await screen.findByRole('heading', { level: 1, name: 'Log in' })

    fillLogin('new@demo', 'a good long password')

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Today' }),
    ).toBeInTheDocument()
  })

  it('shows the server message for a wrong email or password', async () => {
    setSession(null)
    server.use(
      http.post('/api/login', () =>
        HttpResponse.json(
          {
            message: 'The email or password is not correct.',
            errors: { email: ['The email or password is not correct.'] },
          },
          { status: 422 },
        ),
      ),
    )
    renderApp('/login')
    await screen.findByRole('heading', { level: 1, name: 'Log in' })

    fillLogin('new@demo', 'a wrong password')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'The email or password is not correct.',
    )
  })

  it('asks the user to wait after too many attempts', async () => {
    setSession(null)
    server.use(
      http.post('/api/login', () =>
        HttpResponse.json(
          { message: 'Too many login attempts.' },
          { status: 429 },
        ),
      ),
    )
    renderApp('/login')
    await screen.findByRole('heading', { level: 1, name: 'Log in' })

    fillLogin('new@demo', 'a wrong password')

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Too many attempts. Wait a minute, then try again.',
    )
  })

  it('checks the fields before it sends the form', async () => {
    setSession(null)
    let calls = 0
    server.use(
      http.post('/api/login', () => {
        calls += 1
        return HttpResponse.json(testUser)
      }),
    )
    renderApp('/login')
    await screen.findByRole('heading', { level: 1, name: 'Log in' })

    fireEvent.click(screen.getByRole('button', { name: 'Log in' }))

    expect(await screen.findByText('Enter your email.')).toBeInTheDocument()
    expect(screen.getByText('Enter your password.')).toBeInTheDocument()
    expect(calls).toBe(0)
  })
})

describe('admin links', () => {
  it('shows the Admin link on Today and More for an admin', async () => {
    setSession(testAdmin)
    const { router } = renderApp('/')

    const header = await screen.findByRole('banner')
    expect(
      within(header).getByRole('link', { name: 'Admin' }),
    ).toBeInTheDocument()

    await router.navigate('/more')
    // Wait for the More page: the router renders the new page a moment later.
    await screen.findByRole('heading', { level: 1, name: 'More' })
    const main = screen.getByRole('main')
    expect(within(main).getByRole('link', { name: 'Admin' })).toHaveAttribute(
      'href',
      '/admin',
    )
  })

  it('hides the Admin link on Today and More for a user', async () => {
    setSession(testUser)
    const { router } = renderApp('/')

    const header = await screen.findByRole('banner')
    expect(
      within(header).queryByRole('link', { name: 'Admin' }),
    ).not.toBeInTheDocument()

    await router.navigate('/more')
    // Wait for the More page: the router renders the new page a moment later.
    await screen.findByRole('heading', { level: 1, name: 'More' })
    const main = screen.getByRole('main')
    expect(
      within(main).queryByRole('link', { name: 'Admin' }),
    ).not.toBeInTheDocument()
  })
})

describe('logout', () => {
  it('logs out from the More page and opens the login page', async () => {
    setSession(testUser)
    let loggedOut = false
    server.use(
      http.post('/api/logout', () => {
        loggedOut = true
        setSession(null)
        return new HttpResponse(null, { status: 204 })
      }),
    )
    renderApp('/more')

    fireEvent.click(await screen.findByRole('button', { name: 'Log out' }))

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Log in' }),
    ).toBeInTheDocument()
    expect(loggedOut).toBe(true)
  })
})
