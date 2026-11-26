import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, type RenderResult } from '@testing-library/react'
import { HttpResponse, http } from 'msw'
import { RouterProvider, createMemoryRouter } from 'react-router'

import { appRoutes } from '../app/appRoutes.tsx'
import { server } from './server.ts'

export interface TestUser {
  id: string
  name: string
  email: string
  role: 'admin' | 'user'
}

export const testAdmin: TestUser = {
  id: 'u-admin',
  name: 'Ada',
  email: 'admin@demo',
  role: 'admin',
}

export const testUser: TestUser = {
  id: 'u-user',
  name: 'Bo',
  email: 'new@demo',
  role: 'user',
}

// The server session for a test: a logged-in user, or none (GET /api/me returns 401).
// The CSRF cookie endpoint answers 204, like Sanctum.
export function setSession(user: TestUser | null): void {
  server.use(
    http.get('/api/me', () =>
      user === null
        ? HttpResponse.json({ message: 'Unauthenticated.' }, { status: 401 })
        : HttpResponse.json(user),
    ),
    http.get(
      '/sanctum/csrf-cookie',
      () => new HttpResponse(null, { status: 204 }),
    ),
  )
}

// Render the full app (login check included) at a path.
export function renderApp(
  path: string,
): RenderResult & { router: ReturnType<typeof createMemoryRouter> } {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  const router = createMemoryRouter(appRoutes, { initialEntries: [path] })
  const result = render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  )
  return { ...result, router }
}
