import type { BrowserOptions, ErrorEvent } from '@sentry/react'

// Browser error reports (ADR 0014). Privacy (privacy.md items 7 and 12): the reports keep the
// user ID only; texts lose emails and link tokens; the request keeps the method and the path.

const ENVIRONMENTS: Record<string, string> = {
  'pondhabits.com': 'production',
  'staging.pondhabits.com': 'staging',
}

const EMAIL = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi
const TOKEN = /([?&](?:token|email)=)[^&\s"']+/gi

function cleanText(text: string): string {
  return text.replace(TOKEN, '$1[removed]').replace(EMAIL, '[email]')
}

function cleanUrl(url: string): string {
  return url.split('?')[0] ?? url
}

function cleanValue(value: unknown): unknown {
  if (typeof value === 'string') return cleanText(value)
  if (Array.isArray(value)) return value.map(cleanValue)
  if (value !== null && typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value).map(([key, item]) => [key, cleanValue(item)]),
    )
  }
  return value
}

export function scrubEvent(event: ErrorEvent): ErrorEvent {
  const clean: ErrorEvent = { ...event }
  if (clean.message !== undefined) clean.message = cleanText(clean.message)
  if (clean.exception?.values !== undefined) {
    clean.exception = {
      values: clean.exception.values.map((value) =>
        value.value === undefined
          ? value
          : { ...value, value: cleanText(value.value) },
      ),
    }
  }
  if (clean.breadcrumbs !== undefined) {
    clean.breadcrumbs = clean.breadcrumbs.map((crumb) => ({
      ...crumb,
      ...(crumb.message === undefined
        ? {}
        : { message: cleanText(crumb.message) }),
      ...(crumb.data === undefined
        ? {}
        : { data: cleanValue(crumb.data) as Record<string, unknown> }),
    }))
  }
  if (clean.request !== undefined) {
    const { method, url } = clean.request
    clean.request = {
      ...(method === undefined ? {} : { method }),
      ...(url === undefined ? {} : { url: cleanUrl(url) }),
    }
  }
  if (clean.user !== undefined) {
    clean.user = clean.user.id === undefined ? {} : { id: clean.user.id }
  }
  return clean
}

export function sentryOptions(
  hostname: string,
  dsn: string | undefined,
): BrowserOptions | undefined {
  const environment = ENVIRONMENTS[hostname]
  if (dsn === undefined || dsn === '' || environment === undefined)
    return undefined
  return {
    dsn,
    environment,
    dataCollection: {
      userInfo: false,
      cookies: false,
      httpHeaders: false,
      httpBodies: [],
      urlQueryParams: false,
    },
    tracesSampleRate: 0,
    beforeSend: scrubEvent,
  }
}
