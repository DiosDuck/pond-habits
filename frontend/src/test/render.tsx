import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, type RenderResult } from '@testing-library/react'
import { RouterProvider, createMemoryRouter } from 'react-router'

import { routes } from '../app/routes.tsx'

// Render the app at a path, with a fresh query client that does not retry.
export function renderAt(path: string): RenderResult {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  const router = createMemoryRouter(routes, { initialEntries: [path] })
  return render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  )
}
