import type { ErrorEvent } from '@sentry/react'
import { describe, expect, it } from 'vitest'

import { scrubEvent, sentryOptions } from './sentry.ts'

// Browser errors go to Sentry (ADR 0014). The reports contain the user ID, never an email,
// a token, cookies or form data (privacy.md items 7 and 12).

// A Sentry DSN has the shape key@host. example.com keeps the sensitive-data check quiet.
const DSN = 'https://public@example.com/2'

describe('sentryOptions', () => {
  it('is off without a DSN (local work and tests)', () => {
    expect(sentryOptions('pondhabits.com', '')).toBeUndefined()
    expect(sentryOptions('pondhabits.com', undefined)).toBeUndefined()
  })

  it('is off on other hosts, for example localhost', () => {
    expect(sentryOptions('localhost', DSN)).toBeUndefined()
    expect(sentryOptions('pondhabits.com.example.net', DSN)).toBeUndefined()
  })

  it('names the environment from the host', () => {
    expect(sentryOptions('pondhabits.com', DSN)?.environment).toBe('production')
    expect(sentryOptions('staging.pondhabits.com', DSN)?.environment).toBe(
      'staging',
    )
  })

  it('collects no personal data, no traces and cleans each event', () => {
    const options = sentryOptions('pondhabits.com', DSN)
    expect(options?.dsn).toBe(DSN)
    // Sentry 11 collects all of these by default. Each one is off here.
    expect(options?.dataCollection).toEqual({
      userInfo: false,
      cookies: false,
      httpHeaders: false,
      httpBodies: [],
      urlQueryParams: false,
    })
    expect(options?.tracesSampleRate).toBe(0)
    expect(options?.beforeSend).toBe(scrubEvent)
  })
})

describe('scrubEvent', () => {
  it('removes emails and link tokens from the message, the exceptions and the breadcrumbs', () => {
    const event: ErrorEvent = {
      type: undefined,
      message: 'Login failed for jane@example.com',
      exception: {
        values: [
          {
            type: 'Error',
            value: 'Bad link /set-password?token=abc123&email=bo@example.com',
          },
        ],
      },
      breadcrumbs: [
        {
          message: 'Typed cy@example.com',
          data: { url: '/api/register?token=abc123' },
        },
      ],
    }

    const text = JSON.stringify(scrubEvent(event))

    expect(text).not.toContain('example.com')
    expect(text).not.toContain('abc123')
    expect(text).toContain('[email]')
  })

  it('keeps only the method and the path of the request', () => {
    const event: ErrorEvent = {
      type: undefined,
      request: {
        method: 'POST',
        url: 'https://pondhabits.com/set-password?token=abc123',
        query_string: 'token=abc123',
        cookies: { 'laravel-session': 'secret-cookie' },
        headers: {
          Cookie: 'laravel-session=secret-cookie',
          'User-Agent': 'test',
        },
        data: { password: 'secret-password' },
      },
    }

    expect(scrubEvent(event).request).toEqual({
      method: 'POST',
      url: 'https://pondhabits.com/set-password',
    })
  })

  it('keeps only the user ID of the user', () => {
    const event: ErrorEvent = {
      type: undefined,
      user: {
        id: 'u-1',
        email: 'jane@example.com',
        username: 'Jane',
        ip_address: '203.0.113.7',
      },
    }

    expect(scrubEvent(event).user).toEqual({ id: 'u-1' })
  })
})
