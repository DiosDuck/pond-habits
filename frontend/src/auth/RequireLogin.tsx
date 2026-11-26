import { useEffect } from 'react'
import { Navigate, Outlet, useLocation } from 'react-router'

import { applyTheme } from '../theme/theme.ts'

import { CurrentUserContext, useMe } from './session.ts'

// The login check for all pages except the public ones (login, set password).
// Without a session the user goes to /login, and returns here after the login.
export function RequireLogin() {
  const me = useMe()
  const location = useLocation()
  const theme = me.data?.theme

  // The theme of the account follows the user to each device.
  useEffect(() => {
    if (theme !== undefined) applyTheme(theme)
  }, [theme])

  if (me.isPending) return null

  if (me.data == null) {
    const next = encodeURIComponent(location.pathname + location.search)
    return <Navigate to={`/login?next=${next}`} replace />
  }

  return (
    <CurrentUserContext value={me.data}>
      <Outlet />
    </CurrentUserContext>
  )
}
