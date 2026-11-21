import { useQuery } from '@tanstack/react-query'

import { fetchHealth } from '../api/health.ts'

// Temporary start page. The design session and the Today page (Phase 5) replace it.
export function HomePage() {
  const health = useQuery({
    queryKey: ['health'],
    queryFn: ({ signal }) => fetchHealth(signal),
  })

  let status: string
  if (health.isPending) status = 'Checking the server…'
  else if (health.isError || health.data.status !== 'ok')
    status = 'The server has a problem.'
  else status = 'The server is ready.'

  return (
    <main className="mx-auto flex min-h-dvh max-w-md flex-col items-center justify-center gap-4 p-6 text-center">
      <h1 className="text-3xl font-bold text-teal-700">Pond Habits</h1>
      <p role="status" className="text-slate-700">
        {status}
      </p>
    </main>
  )
}
