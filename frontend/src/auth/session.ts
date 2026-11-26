import { useQuery } from '@tanstack/react-query'
import { createContext, useContext } from 'react'

import { fetchMe, type User } from './api.ts'

export const meQueryKey = ['me'] as const

// The session from GET /api/me. The answer stays valid until login or logout changes it.
export function useMe() {
  return useQuery({
    queryKey: meQueryKey,
    queryFn: fetchMe,
    staleTime: Infinity,
  })
}

// The logged-in user for the pages behind the login check. Null outside of it.
export const CurrentUserContext = createContext<User | null>(null)

export function useCurrentUser(): User | null {
  return useContext(CurrentUserContext)
}

// A return path after login: only a path of this app. "//host" or "/\host" would leave the app.
export function safeNextPath(next: string | null): string {
  if (next === null || !next.startsWith('/') || /^\/[/\\]/.test(next))
    return '/'
  return next
}
