import { z } from 'zod'

import { apiGet, apiPost } from '../api/client.ts'

// The user JSON of GET /api/me and POST /api/login (backend UserResource).
const userSchema = z.object({
  id: z.string(),
  name: z.string(),
  email: z.string(),
  role: z.enum(['admin', 'user']),
})

export type User = z.infer<typeof userSchema>

// The logged-in user, or null when there is no session.
export async function fetchMe(): Promise<User | null> {
  const body = await apiGet('/api/me')
  return body === null ? null : userSchema.parse(body)
}

export async function login(email: string, password: string): Promise<User> {
  return userSchema.parse(await apiPost('/api/login', { email, password }))
}

export async function logout(): Promise<void> {
  await apiPost('/api/logout')
}

export async function setPassword(input: {
  token: string
  email: string
  password: string
}): Promise<void> {
  await apiPost('/api/password/set', {
    token: input.token,
    email: input.email,
    password: input.password,
    password_confirmation: input.password,
  })
}
