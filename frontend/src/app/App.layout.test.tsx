import { render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

import { setSession, testUser } from '../test/renderApp.tsx'
import { App } from './App.tsx'
import { createQueryClient } from './queryClient.ts'

// jsdom has no service worker. The update prompt stays closed.
vi.mock('virtual:pwa-register/react', () => ({
  useRegisterSW: () => ({
    needRefresh: [false, vi.fn()],
    offlineReady: [false, vi.fn()],
    updateServiceWorker: vi.fn(),
  }),
}))

describe('App', () => {
  it('opens the Today page at / for a logged-in user', async () => {
    setSession(testUser)
    render(<App />)

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Today' }),
    ).toBeInTheDocument()
  })

  it('makes a query client with one retry', () => {
    const client = createQueryClient()

    expect(client.getDefaultOptions().queries?.retry).toBe(1)
  })
})
