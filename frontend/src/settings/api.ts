import { z } from 'zod'

import {
  apiDelete,
  apiDownload,
  apiGet,
  apiPatch,
  apiPost,
} from '../api/client.ts'
import type { Theme } from '../theme/theme.ts'

// The Settings API (docs/product/settings.md).

const settingsSchema = z.object({
  name: z.string(),
  email: z.string(),
  timezone: z.string(),
  theme: z.enum(['system', 'light', 'dark']),
})

export type Settings = z.infer<typeof settingsSchema>

export async function getSettings(): Promise<Settings> {
  return settingsSchema.parse(await apiGet('/api/settings'))
}

export async function updateSettings(
  change: Partial<{ name: string; timezone: string; theme: Theme }>,
): Promise<Settings> {
  return settingsSchema.parse(await apiPatch('/api/settings', change))
}

export async function changePassword(input: {
  current: string
  password: string
}): Promise<void> {
  await apiPost('/api/settings/password', {
    current_password: input.current,
    password: input.password,
    password_confirmation: input.password,
  })
}

// Download the own data. The browser saves the file with the name from the server.
export async function downloadMyData(): Promise<void> {
  const { blob, fileName } = await apiDownload('/api/settings/export')
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = fileName ?? 'pond-habits-export.json'
  link.click()
  URL.revokeObjectURL(url)
}

export async function deleteAccount(password: string): Promise<void> {
  await apiDelete('/api/settings/account', { password })
}
