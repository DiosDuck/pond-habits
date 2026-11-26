import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

// The frame of the pages without a login: the app name and one card. No navigation.
export function PublicPage({
  title,
  children,
}: {
  title: string
  children: ReactNode
}) {
  const { t } = useTranslation()

  return (
    <div className="mx-auto flex min-h-dvh max-w-sm flex-col justify-center gap-6 px-4 py-10">
      <div className="flex items-center gap-3">
        <span className="bg-primary flex size-12 items-center justify-center rounded-xl">
          {/* Placeholder duck: mdi:duck, Apache 2.0 (ADR 0024). The artist's duck replaces it. */}
          <svg
            width="32"
            height="32"
            viewBox="0 0 24 24"
            fill="var(--color-accent)"
            aria-hidden="true"
            focusable="false"
          >
            <path d="M8.5 5A1.5 1.5 0 0 0 7 6.5A1.5 1.5 0 0 0 8.5 8A1.5 1.5 0 0 0 10 6.5A1.5 1.5 0 0 0 8.5 5M10 2a5 5 0 0 1 5 5c0 1.7-.85 3.2-2.14 4.1c1.58.15 3.36.51 5.14 1.4c3 1.5 4-.5 4-.5s-1 9-7 9H9s-5 0-5-5c0-3 3-4 2-6c-4 0-4-3.5-4-3.5c1 .5 2.24.5 3 .15A5.02 5.02 0 0 1 10 2" />
          </svg>
        </span>
        <span className="font-display text-xl font-extrabold">
          {t('app.name')}
        </span>
      </div>
      <main className="border-line bg-card flex flex-col gap-5 rounded-2xl border p-5">
        <h1 className="font-display text-2xl font-extrabold">{title}</h1>
        {children}
      </main>
    </div>
  )
}
