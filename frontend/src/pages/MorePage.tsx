import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'

import { PageHeader } from '../layout/PageHeader.tsx'
import { Icon, type IconName } from '../ui/Icon.tsx'

// The pages that are not in the tab bar (ADR 0024).
// Credits history and Log out come with their features.
export function MorePage() {
  const { t } = useTranslation()
  const links: { to: string; label: string; icon: IconName }[] = [
    { to: '/nutrition', label: t('pages.nutrition'), icon: 'nutrition' },
    { to: '/rewards', label: t('pages.rewards'), icon: 'redeem' },
    { to: '/settings', label: t('pages.settings'), icon: 'settings' },
    { to: '/admin', label: t('pages.admin'), icon: 'shield_person' },
  ]

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={t('pages.more')} />
      <main className="px-4">
        <ul className="border-line bg-card divide-line divide-y rounded-2xl border">
          {links.map((link) => (
            <li key={link.to}>
              <Link
                to={link.to}
                className="hover:text-primary flex min-h-14 items-center gap-4 px-4"
              >
                <Icon name={link.icon} className="text-primary" />
                <span>{link.label}</span>
              </Link>
            </li>
          ))}
        </ul>
      </main>
    </div>
  )
}
