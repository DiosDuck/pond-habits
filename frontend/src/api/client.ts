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

// One CSRF cookie request at a time. Two requests at the same time would each start a
// new session with its own token, and one of them would get status 419.
let csrfRequest: Promise<unknown> | null = null

function refreshCsrfCookie(): Promise<unknown> {
  csrfRequest ??= fetch('/sanctum/csrf-cookie', {
    credentials: 'same-origin',
  }).finally(() => {
    csrfRequest = null
  })
  return csrfRequest
}

function send(
  method: 'POST' | 'DELETE',
  path: string,
  body: unknown,
): Promise<Response> {
  const token = readCookie('XSRF-TOKEN')
  return fetch(path, {
    method,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token === null ? {} : { 'X-XSRF-TOKEN': token }),
    },
    ...(body === undefined ? {} : { body: JSON.stringify(body) }),
  })
}

// POST JSON. It gets the CSRF cookie first when the browser has none.
// Status 419 means that the token is old (for example after 30 days with no use):
// it gets a new CSRF cookie and sends the request again, one time.
// Returns the JSON body, or null for "204 No Content".
export function apiPost(path: string, body?: unknown): Promise<unknown> {
  return sendWithCsrf('POST', path, body)
}

// DELETE, with the same CSRF handling as apiPost.
export function apiDelete(path: string): Promise<unknown> {
  return sendWithCsrf('DELETE', path, undefined)
}

async function sendWithCsrf(
  method: 'POST' | 'DELETE',
  path: string,
  body: unknown,
): Promise<unknown> {
  if (readCookie('XSRF-TOKEN') === null) await refreshCsrfCookie()
  let response = await send(method, path, body)
  if (response.status === 419) {
    await refreshCsrfCookie()
    response = await send(method, path, body)
  }
  if (!response.ok)
    throw new ApiError(response.status, await readBody(response))
  return response.status === 204 ? null : response.json()
}
