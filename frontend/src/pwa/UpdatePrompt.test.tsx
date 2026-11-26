import { fireEvent, render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { UpdatePrompt } from './UpdatePrompt.tsx'

// The update prompt (ADR 0024): a new version waits until the user chooses "Reload".
// The vite-plugin-pwa hook is replaced, because jsdom has no service worker.
const sw = vi.hoisted(() => ({
  needRefresh: false,
  setNeedRefresh: vi.fn(),
  updateServiceWorker: vi.fn(),
}))

vi.mock('virtual:pwa-register/react', () => ({
  useRegisterSW: () => ({
    needRefresh: [sw.needRefresh, sw.setNeedRefresh],
    offlineReady: [false, vi.fn()],
    updateServiceWorker: sw.updateServiceWorker,
  }),
}))

beforeEach(() => {
  sw.needRefresh = false
})

describe('UpdatePrompt', () => {
  it('shows nothing when no new version waits', () => {
    render(<UpdatePrompt />)

    expect(screen.queryByText('A new version is ready')).not.toBeInTheDocument()
  })

  it('shows the message when a new version waits', () => {
    sw.needRefresh = true
    render(<UpdatePrompt />)

    expect(screen.getByRole('status')).toHaveTextContent(
      'A new version is ready',
    )
    expect(screen.getByRole('button', { name: 'Reload' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Later' })).toBeInTheDocument()
  })

  it('activates the new version and reloads on "Reload"', () => {
    sw.needRefresh = true
    render(<UpdatePrompt />)

    fireEvent.click(screen.getByRole('button', { name: 'Reload' }))

    expect(sw.updateServiceWorker).toHaveBeenCalledWith(true)
  })

  it('closes the message on "Later" and does not reload', () => {
    sw.needRefresh = true
    render(<UpdatePrompt />)

    fireEvent.click(screen.getByRole('button', { name: 'Later' }))

    expect(sw.setNeedRefresh).toHaveBeenCalledWith(false)
    expect(sw.updateServiceWorker).not.toHaveBeenCalled()
  })
})
