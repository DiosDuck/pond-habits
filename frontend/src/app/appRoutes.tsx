import type { RouteObject } from 'react-router'

import { RequireLogin } from '../auth/RequireLogin.tsx'
import { PageLoading } from '../layout/PageLoading.tsx'
import { routes } from './routes.tsx'

// The full route list of the app: the public pages, then all other pages behind the login check.
// The public pages load on demand: a logged-in user never needs them.
export const appRoutes: RouteObject[] = [
  {
    // Shown during the first load of a lazy page.
    HydrateFallback: PageLoading,
    children: [
      {
        path: '/login',
        lazy: async () => ({
          Component: (await import('../auth/LoginPage.tsx')).LoginPage,
        }),
      },
      {
        path: '/set-password',
        lazy: async () => ({
          Component: (await import('../auth/SetPasswordPage.tsx'))
            .SetPasswordPage,
        }),
      },
      {
        path: '/forgot-password',
        lazy: async () => ({
          Component: (await import('../auth/ForgotPasswordPage.tsx'))
            .ForgotPasswordPage,
        }),
      },
      {
        path: '/register',
        lazy: async () => ({
          Component: (await import('../auth/RegisterPage.tsx')).RegisterPage,
        }),
      },
      { element: <RequireLogin />, children: routes },
    ],
  },
]
