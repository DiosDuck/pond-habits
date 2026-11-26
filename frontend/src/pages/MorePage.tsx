import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router'

import { logout } from '../auth/api.ts'
import { meQueryKey, useCurrentUser } from '../auth/session.ts'
import { PageHeader } from '../layout/PageHeader.tsx'
import { Icon, type IconName } from '../ui/Icon.tsx'

// The pages that are not in the tab bar (ADR 0024), and Log out.
// Credits history comes with its feature.
export function MorePage() {
  const { t } = useTranslation()
  const isAdmin = useCurrentUser()?.role === 'admin'
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  const links: { to: string; label: string; icon: IconName }[] = [
    { to: '/nutrition', label: t('pages.nutrition'), icon: 'nutrition' },
    { to: '/rewards', label: t('pages.rewards'), icon: 'redeem' },
    { to: '/settings', label: t('pages.settings'), icon: 'settings' },
    ...(isAdmin
      ? [
          {
            to: '/admin',
            label: t('pages.admin'),
            icon: 'shield_person' as const,
          },
        ]
      : []),
  ]

  const logoutMutation = useMutation({
    mutationFn: logout,
    onSuccess: async () => {
      queryClient.setQueryData(meQueryKey, null)
      await navigate('/login', { replace: true })
    },
  })

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={t('pages.more')} />
      <main className="flex flex-col gap-4 px-4">
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
        {logoutMutation.isError ? (
          <p role="alert" className="text-minus text-sm font-semibold">
            {t('common.failed')}
          </p>
        ) : null}
        <button
          type="button"
          onClick={() => {
            logoutMutation.mutate()
          }}
          disabled={logoutMutation.isPending}
          className="border-line bg-card hover:text-primary min-h-12 rounded-2xl border font-semibold disabled:opacity-60"
        >
          {t('login.logout')}
        </button>
      </main>
    </div>
  )
}
