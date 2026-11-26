import { Navigate, Outlet, useLocation } from 'react-router'

import { CurrentUserContext, useMe } from './session.ts'

// The login check for all pages except the public ones (login, set password).
// Without a session the user goes to /login, and returns here after the login.
export function RequireLogin() {
  const me = useMe()
  const location = useLocation()

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
