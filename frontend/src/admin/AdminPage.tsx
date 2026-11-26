import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'

import { ApiError } from '../api/client.ts'
import { FormField } from '../auth/FormField.tsx'
import { useCurrentUser } from '../auth/session.ts'
import { PageHeader } from '../layout/PageHeader.tsx'
import { sendInvite } from './api.ts'

// The Admin page (docs/product/accounts.md). Now: send an invite (docs/product/invites.md).
// The lists of invites and users come in a later PR.
// The server checks the admin role for each request. This page only hides the form.
export function AdminPage() {
  const { t } = useTranslation()
  const isAdmin = useCurrentUser()?.role === 'admin'

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={t('pages.admin')} />
      <main className="flex flex-col gap-4 px-4">
        {isAdmin ? (
          <InviteForm />
        ) : (
          <p className="border-line bg-card text-muted rounded-2xl border p-4">
            {t('admin.onlyAdmins')}
          </p>
        )}
      </main>
    </div>
  )
}

function InviteForm() {
  const { t } = useTranslation()
  const schema = z.object({
    email: z.string().trim().min(1, t('login.enterEmail')),
  })
  type Values = z.infer<typeof schema>

  const form = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { email: '' },
  })

  const mutation = useMutation({
    mutationFn: (values: Values) => sendInvite(values.email),
    onSuccess: () => {
      form.reset()
    },
    onError: (error) => {
      const message =
        error instanceof ApiError ? error.fieldError('email') : undefined
      if (message !== undefined) form.setError('email', { message })
    },
  })

  const generalError =
    mutation.error === null
      ? null
      : mutation.error instanceof ApiError && mutation.error.status === 429
        ? t('admin.tooMany')
        : mutation.error instanceof ApiError &&
            mutation.error.fieldError('email') !== undefined
          ? null
          : t('common.failed')

  return (
    <section className="border-line bg-card flex flex-col gap-4 rounded-2xl border p-4">
      <div>
        <h2 className="font-semibold">{t('admin.inviteTitle')}</h2>
        <p className="text-muted text-sm">{t('admin.inviteHint')}</p>
      </div>
      <form
        noValidate
        onSubmit={(event) => {
          // handleSubmit returns a Promise. React does not wait for it.
          void form.handleSubmit((values) => {
            mutation.mutate(values)
          })(event)
        }}
        className="flex flex-col gap-4"
      >
        <FormField
          label={t('admin.email')}
          type="email"
          autoComplete="off"
          error={form.formState.errors.email?.message}
          {...form.register('email')}
        />
        {mutation.isSuccess ? (
          <p role="status" className="text-primary text-sm font-semibold">
            {t('admin.sent', { email: mutation.data.email })}
          </p>
        ) : null}
        {generalError === null ? null : (
          <p
            role="alert"
            className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
          >
            {generalError}
          </p>
        )}
        <button
          type="submit"
          disabled={mutation.isPending}
          className="bg-primary text-on-primary min-h-12 rounded-xl font-semibold disabled:opacity-60"
        >
          {t('admin.send')}
        </button>
      </form>
    </section>
  )
}
