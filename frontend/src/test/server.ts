import { HttpResponse, http } from 'msw'
import { setupServer } from 'msw/node'

// Mock Service Worker (MSW) answers the API calls in component tests (ADR 0008).
// The default handlers answer like a healthy server. A test can override them with server.use().
const handlers = [
  http.get('/health', () =>
    HttpResponse.json({ status: 'ok', checks: { database: 'ok' } }),
  ),
]

export const server = setupServer(...handlers)
