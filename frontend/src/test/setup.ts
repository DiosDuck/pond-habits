import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterAll, afterEach, beforeAll } from 'vitest'

import { server } from './server.ts'

// jsdom has no base URL for relative fetch() calls. Use the local Caddy origin.
const origin = 'http://localhost:8080'
const realFetch = globalThis.fetch
globalThis.fetch = (input, init) =>
  realFetch(
    typeof input === 'string' && input.startsWith('/') ? origin + input : input,
    init,
  )

beforeAll(() => {
  // A request with no handler fails the test, so no test calls a real server by mistake.
  server.listen({ onUnhandledFrame: 'error' })
})
afterEach(() => {
  server.resetHandlers()
  cleanup()
})
afterAll(() => {
  server.close()
})
