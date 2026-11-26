import type { RouteObject } from 'react-router'

import '../i18n/i18n.ts'
import { AppLayout } from '../layout/AppLayout.tsx'
import { PageLoading } from '../layout/PageLoading.tsx'
import { MorePage } from '../pages/MorePage.tsx'
import { NotFoundPage } from '../pages/NotFoundPage.tsx'
import { TodayPage } from '../pages/TodayPage.tsx'

// Pages of features that come in a later version (ADR 0024).
const later = ['focus', 'tasks', 'habits', 'nutrition', 'rewards'] as const

// Today, More and "Page not found" are in the first download: the app opens on them.
// Each feature page loads on demand ("lazy"), so the first download stays small
// (budget: 200 KB gzip, scripts/check-build.mjs). Each page shows its h1 as soon as it loads.
export const routes: RouteObject[] = [
  {
    element: <AppLayout />,
    HydrateFallback: PageLoading,
    children: [
      { index: true, element: <TodayPage /> },
      ...later.map((page): RouteObject => ({
        path: page,
        lazy: async () => {
          const { PlaceholderPage } =
            await import('../pages/PlaceholderPage.tsx')
          return { element: <PlaceholderPage page={page} /> }
        },
      })),
      {
        path: 'settings',
        lazy: async () => ({
          Component: (await import('../settings/SettingsPage.tsx'))
            .SettingsPage,
        }),
      },
      {
        path: 'admin',
        lazy: async () => ({
          Component: (await import('../admin/AdminPage.tsx')).AdminPage,
        }),
      },
      {
        path: 'admin/users',
        lazy: async () => ({
          Component: (await import('../admin/AdminUsersPage.tsx'))
            .AdminUsersPage,
        }),
      },
      {
        path: 'admin/invites',
        lazy: async () => ({
          Component: (await import('../admin/AdminInvitesPage.tsx'))
            .AdminInvitesPage,
        }),
      },
      { path: 'more', element: <MorePage /> },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
]
