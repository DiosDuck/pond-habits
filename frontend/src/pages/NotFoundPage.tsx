import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'

import { PageHeader } from '../layout/PageHeader.tsx'

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={t('notFound.title')} />
      <main className="px-4">
        <Link
          to="/"
          className="bg-primary text-on-primary inline-flex min-h-11 items-center rounded-xl px-4 font-semibold"
        >
          {t('notFound.back')}
        </Link>
      </main>
    </div>
  )
}
