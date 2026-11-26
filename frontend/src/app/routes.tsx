import type { RouteObject } from 'react-router'

import '../i18n/i18n.ts'
import { AppLayout } from '../layout/AppLayout.tsx'
import { MorePage } from '../pages/MorePage.tsx'
import { NotFoundPage } from '../pages/NotFoundPage.tsx'
import { PlaceholderPage } from '../pages/PlaceholderPage.tsx'
import { TodayPage } from '../pages/TodayPage.tsx'

// Pages of features that come in a later version (ADR 0024).
const later = [
  'focus',
  'tasks',
  'habits',
  'nutrition',
  'rewards',
  'settings',
  'admin',
] as const

export const routes: RouteObject[] = [
  {
    element: <AppLayout />,
    children: [
      { index: true, element: <TodayPage /> },
      ...later.map((page) => ({
        path: page,
        element: <PlaceholderPage page={page} />,
      })),
      { path: 'more', element: <MorePage /> },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
]
