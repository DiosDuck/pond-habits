import type { ParseKeys } from 'i18next'
import { useTranslation } from 'react-i18next'

import { PageHeader } from '../layout/PageHeader.tsx'

interface PlaceholderPageProps {
  // The i18n key of the page name, written in full (src/i18n/keys.test.ts).
  title: ParseKeys
}

// A page of a feature that comes in a later version. The navigation works now.
export function PlaceholderPage({ title }: PlaceholderPageProps) {
  const { t } = useTranslation()

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={t(title)} />
      <main className="px-4">
        <p className="border-line bg-card text-muted rounded-2xl border p-4">
          {t('pages.later')}
        </p>
      </main>
    </div>
  )
}
