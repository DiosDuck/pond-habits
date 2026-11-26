import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'

import { useCurrentUser } from '../auth/session.ts'

// The frame of the admin list pages: a link back to Admin, the page name, and the content
// for admins only. The server checks the role again for each request.
export function AdminSubPage({
  title,
  children,
}: {
  title: string
  children: ReactNode
}) {
  const { t } = useTranslation()
  const isAdmin = useCurrentUser()?.role === 'admin'

  return (
    <div className="mx-auto max-w-3xl">
      <header className="flex flex-col gap-1 px-5 pt-3 pb-3">
        <Link
          to="/admin"
          className="text-primary flex min-h-11 items-center gap-1 self-start text-sm font-semibold"
        >
          <span aria-hidden="true">‹</span>
          {t('admin.back')}
        </Link>
        <h1 className="font-display text-3xl font-extrabold">{title}</h1>
      </header>
      <main className="flex flex-col gap-3 px-4">
        {isAdmin ? (
          children
        ) : (
          <p className="border-line bg-card text-muted rounded-2xl border p-4">
            {t('admin.onlyAdmins')}
          </p>
        )}
      </main>
    </div>
  )
}
