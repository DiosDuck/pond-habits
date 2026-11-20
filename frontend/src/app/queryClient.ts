import { QueryClient } from '@tanstack/react-query'

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      // Mobile networks fail often. One retry is enough, more makes the UI feel slow.
      queries: { retry: 1, staleTime: 30_000 },
    },
  })
}
