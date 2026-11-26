import { fireEvent, screen, within } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { renderApp, setSession, testUser } from '../test/renderApp.tsx'
import { server } from '../test/server.ts'

// The Settings page (docs/product/settings.md).

interface Settings {
  name: string
  email: string
  timezone: string
  theme: 'system' | 'light' | 'dark'
}

const deviceZone = Intl.DateTimeFormat().resolvedOptions().timeZone

function settingsServer(initial: Partial<Settings> = {}): {
  patches: unknown[]
} {
  let settings: Settings = {
    name: 'Bo',
    email: 'new@demo',
    timezone: 'Pacific/Auckland',
    theme: 'system',
    ...initial,
  }
  const patches: unknown[] = []
  setSession(testUser)
  server.use(
    http.get('/api/settings', () => HttpResponse.json(settings)),
    http.patch('/api/settings', async ({ request }) => {
      const body = (await request.json()) as Partial<Settings>
      patches.push(body)
      settings = { ...settings, ...body }
      return HttpResponse.json(settings)
    }),
  )
  return { patches }
}

async function openSettings(): Promise<void> {
  renderApp('/settings')
  await screen.findByDisplayValue('Bo')
}

afterEach(() => {
  document.documentElement.removeAttribute('data-theme')
  localStorage.clear()
})

describe('Profile', () => {
  it('shows the name and the time zone of the user', async () => {
    settingsServer()
    await openSettings()

    expect(screen.getByLabelText('Time zone')).toHaveValue('Pacific/Auckland')
  })

  it('saves the name and the time zone with one Save', async () => {
    const { patches } = settingsServer()
    await openSettings()

    fireEvent.change(screen.getByLabelText('Name'), {
      target: { value: 'Bo Pond' },
    })
    fireEvent.change(screen.getByLabelText('Time zone'), {
      target: { value: 'Europe/Bucharest' },
    })
    fireEvent.click(screen.getByRole('button', { name: 'Save' }))

    expect(await screen.findByRole('status')).toHaveTextContent('Saved.')
    expect(patches).toEqual([{ name: 'Bo Pond', timezone: 'Europe/Bucharest' }])
  })

  it("sets the device's time zone with one click", async () => {
    settingsServer()
    await openSettings()

    fireEvent.click(
      screen.getByRole('button', { name: /Use this device's time zone/ }),
    )

    expect(screen.getByLabelText('Time zone')).toHaveValue(deviceZone)
  })

  it('asks for a name', async () => {
    const { patches } = settingsServer()
    await openSettings()

    fireEvent.change(screen.getByLabelText('Name'), { target: { value: '' } })
    fireEvent.click(screen.getByRole('button', { name: 'Save' }))

    expect(await screen.findByText('Enter your name.')).toBeInTheDocument()
    expect(patches).toEqual([])
  })
})

describe('Theme', () => {
  it('changes the theme at once and saves it', async () => {
    const { patches } = settingsServer()
    await openSettings()

    fireEvent.click(screen.getByRole('radio', { name: 'Dark' }))

    expect(document.documentElement.dataset['theme']).toBe('dark')
    expect(localStorage.getItem('theme')).toBe('dark')
    expect(screen.getByRole('radio', { name: 'Dark' })).toHaveAttribute(
      'aria-checked',
      'true',
    )
    await vi.waitFor(() => {
      expect(patches).toEqual([{ theme: 'dark' }])
    })
  })

  it('removes the theme attribute for "System"', async () => {
    settingsServer({ theme: 'dark' })
    document.documentElement.dataset['theme'] = 'dark'
    await openSettings()

    fireEvent.click(screen.getByRole('radio', { name: 'System' }))

    expect(document.documentElement.hasAttribute('data-theme')).toBe(false)
    expect(localStorage.getItem('theme')).toBe('system')
  })
})

describe('Password', () => {
  function fillPassword(current: string, next: string, repeat = next): void {
    fireEvent.change(screen.getByLabelText('Current password'), {
      target: { value: current },
    })
    fireEvent.change(screen.getByLabelText('New password'), {
      target: { value: next },
    })
    fireEvent.change(screen.getByLabelText('Repeat the new password'), {
      target: { value: repeat },
    })
    fireEvent.click(screen.getByRole('button', { name: 'Change password' }))
  }

  it('changes the password and opens the login page', async () => {
    settingsServer()
    let sent: unknown = null
    server.use(
      http.post('/api/settings/password', async ({ request }) => {
        sent = await request.json()
        setSession(null)
        return new HttpResponse(null, { status: 204 })
      }),
    )
    await openSettings()

    fillPassword('a good long password', 'a brand new long password')

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Log in' }),
    ).toBeInTheDocument()
    expect(sent).toEqual({
      current_password: 'a good long password',
      password: 'a brand new long password',
      password_confirmation: 'a brand new long password',
    })
  })

  it('shows the server message for a wrong current password', async () => {
    settingsServer()
    server.use(
      http.post('/api/settings/password', () =>
        HttpResponse.json(
          {
            message: 'The current password is not correct.',
            errors: {
              current_password: ['The current password is not correct.'],
            },
          },
          { status: 422 },
        ),
      ),
    )
    await openSettings()

    fillPassword('not the password', 'a brand new long password')

    expect(
      await screen.findByText('The current password is not correct.'),
    ).toBeInTheDocument()
  })

  it('checks the new password before it sends the form', async () => {
    settingsServer()
    let calls = 0
    server.use(
      http.post('/api/settings/password', () => {
        calls += 1
        return new HttpResponse(null, { status: 204 })
      }),
    )
    await openSettings()

    fillPassword('a good long password', 'short pass', 'other text')

    expect(
      await screen.findByText('Use 12 characters or more.'),
    ).toBeInTheDocument()
    expect(screen.getByText('The passwords do not match.')).toBeInTheDocument()
    expect(calls).toBe(0)
  })
})

describe('Your data', () => {
  it('downloads the JSON file with the name from the server', async () => {
    settingsServer()
    server.use(
      http.get(
        '/api/settings/export',
        () =>
          new HttpResponse(JSON.stringify({ user: { name: 'Bo' } }), {
            headers: {
              'Content-Type': 'application/json',
              'Content-Disposition':
                'attachment; filename=pond-habits-export-2026-11-26.json',
            },
          }),
      ),
    )
    // jsdom has no object URLs: the test gives them.
    URL.createObjectURL = vi.fn(() => 'blob:export')
    URL.revokeObjectURL = vi.fn()
    const clicks: string[] = []
    vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function (
      this: HTMLAnchorElement,
    ) {
      clicks.push(this.download)
    })
    await openSettings()

    fireEvent.click(screen.getByRole('button', { name: 'Download my data' }))

    await vi.waitFor(() => {
      expect(clicks).toEqual(['pond-habits-export-2026-11-26.json'])
    })
  })
})

describe('Delete account', () => {
  async function openConfirm(): Promise<HTMLElement> {
    fireEvent.click(screen.getByRole('button', { name: 'Delete my account' }))
    return screen.findByRole('dialog', { name: 'Delete your account?' })
  }

  it('deletes the account after the password and opens the login page', async () => {
    settingsServer()
    let sent: unknown = null
    server.use(
      http.delete('/api/settings/account', async ({ request }) => {
        sent = await request.json()
        setSession(null)
        return new HttpResponse(null, { status: 204 })
      }),
    )
    await openSettings()

    const dialog = await openConfirm()
    fireEvent.change(within(dialog).getByLabelText('Password'), {
      target: { value: 'a good long password' },
    })
    fireEvent.click(
      within(dialog).getByRole('button', { name: 'Delete my account' }),
    )

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Log in' }),
    ).toBeInTheDocument()
    expect(sent).toEqual({ password: 'a good long password' })
  })

  it('shows the server message in the confirm', async () => {
    settingsServer()
    server.use(
      http.delete('/api/settings/account', () =>
        HttpResponse.json(
          {
            message: 'You are the only admin. Another admin must exist first.',
            errors: {
              account: [
                'You are the only admin. Another admin must exist first.',
              ],
            },
          },
          { status: 422 },
        ),
      ),
    )
    await openSettings()

    const dialog = await openConfirm()
    fireEvent.change(within(dialog).getByLabelText('Password'), {
      target: { value: 'a good long password' },
    })
    fireEvent.click(
      within(dialog).getByRole('button', { name: 'Delete my account' }),
    )

    expect(await within(dialog).findByRole('alert')).toHaveTextContent(
      'You are the only admin. Another admin must exist first.',
    )
  })

  it('closes the confirm with Cancel and deletes nothing', async () => {
    settingsServer()
    let calls = 0
    server.use(
      http.delete('/api/settings/account', () => {
        calls += 1
        return new HttpResponse(null, { status: 204 })
      }),
    )
    await openSettings()

    const dialog = await openConfirm()
    fireEvent.click(within(dialog).getByRole('button', { name: 'Cancel' }))

    await screen.findByDisplayValue('Bo')
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    expect(calls).toBe(0)
  })
})
