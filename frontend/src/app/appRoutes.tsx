import type { RouteObject } from 'react-router'

import { LoginPage } from '../auth/LoginPage.tsx'
import { RegisterPage } from '../auth/RegisterPage.tsx'
import { RequireLogin } from '../auth/RequireLogin.tsx'
import { SetPasswordPage } from '../auth/SetPasswordPage.tsx'
import { routes } from './routes.tsx'

// The full route list of the app: the public pages, then all other pages behind the login check.
export const appRoutes: RouteObject[] = [
  { path: '/login', element: <LoginPage /> },
  { path: '/set-password', element: <SetPasswordPage /> },
  { path: '/register', element: <RegisterPage /> },
  { element: <RequireLogin />, children: routes },
]
