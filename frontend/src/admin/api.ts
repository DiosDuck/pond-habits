import { z } from 'zod'

import { apiDelete, apiGet, apiPost } from '../api/client.ts'

// The admin API (docs/product/admin.md). The server checks the admin role for each request.

const inviteSchema = z.object({
  id: z.string(),
  email: z.string(),
  expires_at: z.string(),
})

export type Invite = z.infer<typeof inviteSchema>

const adminUserSchema = z.object({
  id: z.string(),
  name: z.string(),
  email: z.string(),
  role: z.enum(['admin', 'user']),
  disabled: z.boolean(),
  joined_at: z.string().nullable(),
})

export type AdminUser = z.infer<typeof adminUserSchema>

export async function sendInvite(email: string): Promise<Invite> {
  return inviteSchema.parse(await apiPost('/api/admin/invites', { email }))
}

export async function listInvites(): Promise<Invite[]> {
  return z.array(inviteSchema).parse(await apiGet('/api/admin/invites'))
}

export async function cancelInvite(id: string): Promise<void> {
  await apiDelete(`/api/admin/invites/${encodeURIComponent(id)}`)
}

export async function listUsers(): Promise<AdminUser[]> {
  return z.array(adminUserSchema).parse(await apiGet('/api/admin/users'))
}

export async function setUserDisabled(
  id: string,
  disabled: boolean,
): Promise<AdminUser> {
  const action = disabled ? 'disable' : 'enable'
  return adminUserSchema.parse(
    await apiPost(`/api/admin/users/${encodeURIComponent(id)}/${action}`),
  )
}
