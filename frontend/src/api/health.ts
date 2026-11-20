export interface Health {
  status: 'ok' | 'fail'
  checks: { database: 'ok' | 'fail' }
}

function isHealth(value: unknown): value is Health {
  if (typeof value !== 'object' || value === null) return false
  const v = value as Record<string, unknown>
  const checks = v['checks'] as Record<string, unknown> | undefined
  return (
    (v['status'] === 'ok' || v['status'] === 'fail') &&
    typeof checks === 'object' &&
    (checks['database'] === 'ok' || checks['database'] === 'fail')
  )
}

// GET /health. A 503 answer still has a body, so read the body for all statuses.
export async function fetchHealth(signal?: AbortSignal): Promise<Health> {
  const response = await fetch('/health', {
    headers: { Accept: 'application/json' },
    ...(signal ? { signal } : {}),
  })
  const body: unknown = await response.json()
  if (!isHealth(body)) {
    throw new Error(
      `Unexpected /health answer (HTTP ${String(response.status)}).`,
    )
  }
  return body
}
