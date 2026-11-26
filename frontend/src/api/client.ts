// A small client for the Laravel API in Sanctum SPA cookie mode (ADR 0005).
// The browser sends the session cookie by itself (same origin).
// Each request that changes data sends the CSRF token from the XSRF-TOKEN cookie.

export interface ApiErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

export class ApiError extends Error {
  readonly status: number
  readonly body: ApiErrorBody

  constructor(status: number, body: ApiErrorBody) {
    super(body.message ?? `HTTP ${String(status)}`)
    this.name = 'ApiError'
    this.status = status
    this.body = body
  }

  // The first message for a field of a 422 answer, for example "email".
  fieldError(field: string): string | undefined {
    return this.body.errors?.[field]?.[0]
  }
}

function readCookie(name: string): string | null {
  const prefix = `${name}=`
  for (const part of document.cookie.split('; ')) {
    if (part.startsWith(prefix)) {
      return decodeURIComponent(part.slice(prefix.length))
    }
  }
  return null
}

async function readBody(response: Response): Promise<ApiErrorBody> {
  try {
    const body: unknown = await response.json()
    return typeof body === 'object' && body !== null ? body : {}
  } catch {
    return {}
  }
}

// GET a JSON resource. Status 401 gives null: there is no session.
export async function apiGet(path: string): Promise<unknown> {
  const response = await fetch(path, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  })
  if (response.status === 401) return null
  if (!response.ok)
    throw new ApiError(response.status, await readBody(response))
  return response.json()
}

// POST JSON. It gets the CSRF cookie first when the browser has none.
// Returns the JSON body, or null for "204 No Content".
export async function apiPost(path: string, body?: unknown): Promise<unknown> {
  if (readCookie('XSRF-TOKEN') === null) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
  }
  const token = readCookie('XSRF-TOKEN')
  const response = await fetch(path, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token === null ? {} : { 'X-XSRF-TOKEN': token }),
    },
    ...(body === undefined ? {} : { body: JSON.stringify(body) }),
  })
  if (!response.ok)
    throw new ApiError(response.status, await readBody(response))
  return response.status === 204 ? null : response.json()
}
