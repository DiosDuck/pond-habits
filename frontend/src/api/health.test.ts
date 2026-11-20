import { HttpResponse, http } from 'msw'
import { describe, expect, it } from 'vitest'

import { server } from '../test/server.ts'
import { fetchHealth } from './health.ts'

describe('fetchHealth', () => {
  it('returns the health body when the server is ok', async () => {
    await expect(fetchHealth()).resolves.toEqual({
      status: 'ok',
      checks: { database: 'ok' },
    })
  })

  it('returns the body of a 503 answer', async () => {
    server.use(
      http.get('/health', () =>
        HttpResponse.json(
          { status: 'fail', checks: { database: 'fail' } },
          { status: 503 },
        ),
      ),
    )

    await expect(fetchHealth()).resolves.toEqual({
      status: 'fail',
      checks: { database: 'fail' },
    })
  })

  it.each([
    ['null', null],
    ['a wrong status', { status: 'maybe', checks: { database: 'ok' } }],
    ['no checks', { status: 'ok' }],
    ['a wrong database value', { status: 'ok', checks: { database: 'up' } }],
  ])('rejects an answer with %s', async (_label, body) => {
    server.use(http.get('/health', () => HttpResponse.json(body)))

    await expect(fetchHealth()).rejects.toThrow('Unexpected /health answer')
  })
})
