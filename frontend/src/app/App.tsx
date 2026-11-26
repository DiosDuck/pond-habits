import { QueryClientProvider } from '@tanstack/react-query'
import { useState } from 'react'
import { RouterProvider, createBrowserRouter } from 'react-router'

import { UpdatePrompt } from '../pwa/UpdatePrompt.tsx'
import { createQueryClient } from './queryClient.ts'
import { appRoutes } from './appRoutes.tsx'

export function App() {
  // useState keeps one client and one router for the life of the app.
  const [queryClient] = useState(createQueryClient)
  const [router] = useState(() => createBrowserRouter(appRoutes))

  return (
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
      <UpdatePrompt />
    </QueryClientProvider>
  )
}
