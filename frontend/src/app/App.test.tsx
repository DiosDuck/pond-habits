import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'

import { App } from './App.tsx'
import { createQueryClient } from './queryClient.ts'

describe('App', () => {
  it('renders the start page at /', async () => {
    render(<App />)

    expect(await screen.findByText('The server is ready.')).toBeInTheDocument()
  })

  it('makes a query client with one retry', () => {
    const client = createQueryClient()

    expect(client.getDefaultOptions().queries?.retry).toBe(1)
  })
})
