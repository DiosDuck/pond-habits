import { useTranslation } from 'react-i18next'

import { PageHeader } from '../layout/PageHeader.tsx'

interface PlaceholderPageProps {
  // The key of the page name under "pages" in en.json.
  page: string
}

// A page of a feature that comes in a later version. The navigation works now.
export function PlaceholderPage({ page }: PlaceholderPageProps) {
  const { t } = useTranslation()

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={t(`pages.${page}`)} />
      <main className="px-4">
        <p className="border-line bg-card text-muted rounded-2xl border p-4">
          {t('pages.later')}
        </p>
      </main>
    </div>
  )
}
