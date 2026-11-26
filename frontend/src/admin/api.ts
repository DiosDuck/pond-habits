import { z } from 'zod'

import { apiPost } from '../api/client.ts'

const inviteSchema = z.object({
  id: z.string(),
  email: z.string(),
  expires_at: z.string(),
})

export type Invite = z.infer<typeof inviteSchema>

// POST /api/admin/invites. The server checks the admin role.
export async function sendInvite(email: string): Promise<Invite> {
  return inviteSchema.parse(await apiPost('/api/admin/invites', { email }))
}
