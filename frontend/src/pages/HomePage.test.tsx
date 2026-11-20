import { screen } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import { renderAt } from '../test/render.tsx'
import { server } from '../test/server.ts'

describe('HomePage', () => {
  it('shows the app name', () => {
    renderAt('/')

    expect(
      screen.getByRole('heading', { name: 'Pond Habits' }),
    ).toBeInTheDocument()
  })

  it('says that the server is ready when /health is ok', async () => {
    renderAt('/')

    expect(screen.getByRole('status')).toHaveTextContent('Checking the server…')
    expect(await screen.findByText('The server is ready.')).toBeInTheDocument()
  })

  it('says that the server has a problem when /health fails', async () => {
    server.use(
      http.get('/health', () =>
        HttpResponse.json(
          { status: 'fail', checks: { database: 'fail' } },
          { status: 503 },
        ),
      ),
    )
    renderAt('/')

    expect(
      await screen.findByText('The server has a problem.'),
    ).toBeInTheDocument()
  })

  it('says that the server has a problem when the network fails', async () => {
    server.use(http.get('/health', () => HttpResponse.error()))
    renderAt('/')

    expect(
      await screen.findByText('The server has a problem.'),
    ).toBeInTheDocument()
  })
})
