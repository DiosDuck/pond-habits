import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useEffect, useId } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { z } from 'zod'

import { ApiError } from '../api/client.ts'
import { FormField } from '../auth/FormField.tsx'
import { meQueryKey } from '../auth/session.ts'
import { deleteAccount } from './api.ts'

// The confirm before "Delete my account" (docs/product/settings.md): the password protects
// an unlocked device. Escape or "Cancel" closes it.
export function DeleteAccountDialog({ onClose }: { onClose: () => void }) {
  const { t } = useTranslation()
  const titleId = useId()
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  const schema = z.object({
    password: z.string().min(1, t('login.enterPassword')),
  })
  type Values = z.infer<typeof schema>
  const form = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { password: '' },
  })

  const mutation = useMutation({
    mutationFn: (values: Values) => deleteAccount(values.password),
    onSuccess: async () => {
      queryClient.setQueryData(meQueryKey, null)
      await navigate('/login', { replace: true })
    },
    onError: (error) => {
      const message =
        error instanceof ApiError ? error.fieldError('password') : undefined
      if (message !== undefined) form.setError('password', { message })
    },
  })

  const accountError =
    mutation.error instanceof ApiError
      ? mutation.error.fieldError('account')
      : undefined

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => {
      window.removeEventListener('keydown', onKey)
    }
  }, [onClose])

  return (
    <div className="fixed inset-0 z-30 flex items-end justify-center bg-black/45 lg:items-center">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        className="bg-card flex w-full max-w-md flex-col gap-4 rounded-t-3xl p-5 pb-8 lg:rounded-3xl"
      >
        <h2 id={titleId} className="font-display text-xl font-extrabold">
          {t('settings.deleteTitle')}
        </h2>
        <ul className="list-disc pl-5 text-sm leading-relaxed">
          <li>{t('settings.deleteAll')}</li>
          <li>{t('settings.deleteLogout')}</li>
          <li>{t('settings.deleteUndo')}</li>
        </ul>
        <form
          noValidate
          onSubmit={(event) => {
            void form.handleSubmit((values) => {
              mutation.mutate(values)
            })(event)
          }}
          className="flex flex-col gap-4"
        >
          <FormField
            label={t('settings.deletePassword')}
            type="password"
            autoComplete="current-password"
            autoFocus
            error={form.formState.errors.password?.message}
            {...form.register('password')}
          />
          {accountError === undefined ? null : (
            <p
              role="alert"
              className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
            >
              {accountError}
            </p>
          )}
          <div className="flex gap-3">
            <button
              type="button"
              onClick={onClose}
              className="border-line min-h-12 flex-1 rounded-xl border font-semibold"
            >
              {t('settings.cancel')}
            </button>
            <button
              type="submit"
              disabled={mutation.isPending}
              className="bg-minus min-h-12 flex-1 rounded-xl text-sm font-semibold text-white disabled:opacity-60"
            >
              {t('settings.deleteButton')}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
