import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'

import { AdminSubPage } from './AdminSubPage.tsx'
import { cancelInvite, listInvites } from './api.ts'
import { shortDate } from './format.ts'

const invitesKey = ['admin', 'invites'] as const

// The pending invites (docs/product/admin.md): cancel at once.
export function AdminInvitesPage() {
  const { t } = useTranslation()
  return (
    <AdminSubPage title={t('admin.invites')}>
      <InvitesList />
    </AdminSubPage>
  )
}

function InvitesList() {
  const { t, i18n } = useTranslation()
  const queryClient = useQueryClient()
  const invites = useQuery({ queryKey: invitesKey, queryFn: listInvites })

  const cancel = useMutation({
    mutationFn: cancelInvite,
    onSettled: async () => {
      await queryClient.invalidateQueries({ queryKey: invitesKey })
    },
  })

  return (
    <>
      <p className="text-muted text-sm">{t('admin.invitesHint')}</p>
      {cancel.isError ? (
        <p
          role="alert"
          className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
        >
          {t('common.failed')}
        </p>
      ) : null}
      {invites.data?.length === 0 ? (
        <p className="border-line text-muted rounded-2xl border border-dashed p-4 text-center text-sm">
          {t('admin.noInvites')}
        </p>
      ) : null}
      {invites.data?.map((invite) => (
        <section
          key={invite.id}
          className="border-line bg-card flex items-center gap-3 rounded-2xl border p-3"
        >
          <div className="flex min-w-0 flex-1 flex-col gap-1">
            <span className="truncate font-semibold">{invite.email}</span>
            <span className="text-muted text-xs">
              {t('admin.expires', {
                date: shortDate(invite.expires_at, i18n.language),
              })}
            </span>
          </div>
          <button
            type="button"
            disabled={cancel.isPending}
            onClick={() => {
              cancel.mutate(invite.id)
            }}
            className="border-line min-h-11 flex-none rounded-xl border px-3.5 text-sm font-semibold"
          >
            {t('admin.cancelInvite')}
          </button>
        </section>
      ))}
    </>
  )
}
