import { HttpResponse, http } from 'msw'
import { afterEach, describe, expect, it } from 'vitest'

import { server } from '../test/server.ts'
import { apiPost } from './client.ts'

// CSRF handling of the API client (Sanctum SPA cookie mode, ADR 0005).
// Laravel compares the X-XSRF-TOKEN header with the token of the session.

function setXsrfCookie(value: string): void {
  document.cookie = `XSRF-TOKEN=${encodeURIComponent(value)}; path=/`
}

afterEach(() => {
  document.cookie = 'XSRF-TOKEN=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT'
})

describe('apiPost CSRF handling', () => {
  it('asks for one CSRF cookie when two requests start at the same time', async () => {
    let csrfCalls = 0
    server.use(
      http.get('/sanctum/csrf-cookie', () => {
        csrfCalls += 1
        setXsrfCookie(`token-${String(csrfCalls)}`)
        return new HttpResponse(null, { status: 204 })
      }),
      http.post('/api/test', () => new HttpResponse(null, { status: 204 })),
    )

    await Promise.all([apiPost('/api/test'), apiPost('/api/test')])

    expect(csrfCalls).toBe(1)
  })

  it('gets a new CSRF cookie and sends the request again after a 419', async () => {
    setXsrfCookie('old-token')
    const headers: (string | null)[] = []
    server.use(
      http.get('/sanctum/csrf-cookie', () => {
        setXsrfCookie('new-token')
        return new HttpResponse(null, { status: 204 })
      }),
      http.post('/api/test', ({ request }) => {
        const token = request.headers.get('X-XSRF-TOKEN')
        headers.push(token)
        return token === 'new-token'
          ? HttpResponse.json({ ok: true })
          : HttpResponse.json(
              { message: 'CSRF token mismatch.' },
              { status: 419 },
            )
      }),
    )

    await expect(apiPost('/api/test')).resolves.toEqual({ ok: true })
    expect(headers).toEqual(['old-token', 'new-token'])
  })

  it('sends the request again only one time', async () => {
    setXsrfCookie('old-token')
    let posts = 0
    server.use(
      http.get(
        '/sanctum/csrf-cookie',
        () => new HttpResponse(null, { status: 204 }),
      ),
      http.post('/api/test', () => {
        posts += 1
        return HttpResponse.json(
          { message: 'CSRF token mismatch.' },
          { status: 419 },
        )
      }),
    )

    await expect(apiPost('/api/test')).rejects.toMatchObject({ status: 419 })
    expect(posts).toBe(2)
  })
})
