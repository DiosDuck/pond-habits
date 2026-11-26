import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import { ApiError } from '../api/client.ts'
import { useCurrentUser } from '../auth/session.ts'
import { AdminSubPage } from './AdminSubPage.tsx'
import { listUsers, setUserDisabled, type AdminUser } from './api.ts'
import { ConfirmDisableDialog } from './ConfirmDisableDialog.tsx'
import { shortDate } from './format.ts'

const usersKey = ['admin', 'users'] as const

// The users list (docs/product/admin.md): disable (after a confirm) and enable.
export function AdminUsersPage() {
  const { t } = useTranslation()
  return (
    <AdminSubPage title={t('admin.users')}>
      <UsersList />
    </AdminSubPage>
  )
}

function UsersList() {
  const { t, i18n } = useTranslation()
  const me = useCurrentUser()
  const queryClient = useQueryClient()
  const [toDisable, setToDisable] = useState<AdminUser | null>(null)

  const users = useQuery({ queryKey: usersKey, queryFn: listUsers })

  const change = useMutation({
    mutationFn: (input: { id: string; disabled: boolean }) =>
      setUserDisabled(input.id, input.disabled),
    onSettled: async () => {
      setToDisable(null)
      await queryClient.invalidateQueries({ queryKey: usersKey })
    },
  })

  const errorText = change.isError
    ? change.error instanceof ApiError
      ? (change.error.fieldError('user') ?? t('common.failed'))
      : t('common.failed')
    : null

  return (
    <>
      {errorText === null ? null : (
        <p
          role="alert"
          className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
        >
          {errorText}
        </p>
      )}
      {users.data?.map((user) => {
        const isMe = user.id === me?.id
        return (
          <section
            key={user.id}
            className={`border-line bg-card flex items-center gap-3 rounded-2xl border p-3 ${user.disabled ? 'opacity-75' : ''}`}
          >
            <div className="flex min-w-0 flex-1 flex-col gap-1">
              <div className="flex flex-wrap items-center gap-1.5">
                <h2 className="font-semibold">{user.name}</h2>
                {user.role === 'admin' ? (
                  <Chip className="bg-primary-soft text-primary">
                    {t('admin.chipAdmin')}
                  </Chip>
                ) : null}
                {isMe ? (
                  <Chip className="bg-accent text-on-accent">
                    {t('admin.chipYou')}
                  </Chip>
                ) : null}
                {user.disabled ? (
                  <Chip className="bg-ground text-muted">
                    {t('admin.chipDisabled')}
                  </Chip>
                ) : null}
              </div>
              <span className="text-muted truncate text-sm">{user.email}</span>
              {user.joined_at === null ? null : (
                <span className="text-muted text-xs">
                  {t('admin.joined', {
                    date: shortDate(user.joined_at, i18n.language),
                  })}
                </span>
              )}
            </div>
            {user.disabled ? (
              <button
                type="button"
                disabled={change.isPending}
                onClick={() => {
                  change.mutate({ id: user.id, disabled: false })
                }}
                className="border-primary text-primary min-h-11 flex-none rounded-xl border px-3.5 text-sm font-semibold"
              >
                {t('admin.enable')}
              </button>
            ) : isMe ? null : (
              <button
                type="button"
                onClick={() => {
                  setToDisable(user)
                }}
                className="text-minus min-h-11 flex-none rounded-xl border border-current/30 px-3.5 text-sm font-semibold"
              >
                {t('admin.disable')}
              </button>
            )}
          </section>
        )
      })}
      {toDisable === null ? null : (
        <ConfirmDisableDialog
          name={toDisable.name}
          busy={change.isPending}
          onConfirm={() => {
            change.mutate({ id: toDisable.id, disabled: true })
          }}
          onCancel={() => {
            setToDisable(null)
          }}
        />
      )}
    </>
  )
}

function Chip({
  className,
  children,
}: {
  className: string
  children: string
}) {
  return (
    <span
      className={`rounded-full px-2 py-0.5 text-xs font-semibold ${className}`}
    >
      {children}
    </span>
  )
}
