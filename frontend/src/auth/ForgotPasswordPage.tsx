import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { z } from 'zod'

import { ApiError } from '../api/client.ts'
import { forgotPassword } from './api.ts'
import { FormField } from './FormField.tsx'
import { PublicPage } from './PublicPage.tsx'

// Ask for a password reset link (docs/product/password-reset.md).
// The answer is always the same, so the page does not tell if the account exists.
export function ForgotPasswordPage() {
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
    mutationFn: (values: Values) => forgotPassword(values.email),
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
        ? t('forgot.tooMany')
        : mutation.error instanceof ApiError &&
            mutation.error.fieldError('email') !== undefined
          ? null
          : t('common.failed')

  return (
    <PublicPage title={t('forgot.title')}>
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
        <p className="text-muted text-sm">{t('forgot.intro')}</p>
        <FormField
          label={t('login.email')}
          type="email"
          autoComplete="username"
          error={form.formState.errors.email?.message}
          {...form.register('email')}
        />
        {mutation.isSuccess ? (
          <p role="status" className="text-primary text-sm font-semibold">
            {t('forgot.sent')}
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
          {t('forgot.submit')}
        </button>
      </form>
      <Link
        to="/login"
        className="text-primary flex min-h-11 items-center justify-center text-sm font-semibold"
      >
        {t('forgot.back')}
      </Link>
    </PublicPage>
  )
}
