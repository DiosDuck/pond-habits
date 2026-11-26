import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { Link, useSearchParams } from 'react-router'
import { z } from 'zod'

import { ApiError } from '../api/client.ts'
import { setPassword } from './api.ts'
import { FormField } from './FormField.tsx'
import { PublicPage } from './PublicPage.tsx'

// The page of the mailed link (docs/product/login.md). The link has the token only,
// so the form asks for the email. The server checks the token when the form is sent.
export function SetPasswordPage() {
  const { t } = useTranslation()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') ?? ''

  const schema = z
    .object({
      // The server checks the address format (Laravel "email" rule).
      email: z.string().trim().min(1, t('login.enterEmail')),
      password: z.string().min(12, t('setPassword.tooShort')),
      repeat: z.string(),
    })
    .refine((values) => values.password === values.repeat, {
      path: ['repeat'],
      message: t('setPassword.noMatch'),
    })
  type Values = z.infer<typeof schema>

  const form = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', repeat: '' },
  })

  const mutation = useMutation({
    mutationFn: (values: Values) =>
      setPassword({ token, email: values.email, password: values.password }),
    onError: (error) => {
      if (!(error instanceof ApiError)) return
      for (const field of ['email', 'password'] as const) {
        const message = error.fieldError(field)
        if (message !== undefined) form.setError(field, { message })
      }
    },
  })

  const linkRejected =
    token === '' ||
    (mutation.error instanceof ApiError &&
      mutation.error.fieldError('token') !== undefined)

  if (mutation.isSuccess) {
    return (
      <PublicPage title={t('setPassword.title')}>
        <p>{t('setPassword.done')}</p>
        <Link
          to="/login"
          className="bg-primary text-on-primary flex min-h-12 items-center justify-center rounded-xl font-semibold"
        >
          {t('login.submit')}
        </Link>
      </PublicPage>
    )
  }

  return (
    <PublicPage title={t('setPassword.title')}>
      {linkRejected ? (
        <p
          role="alert"
          className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
        >
          {t('setPassword.linkRejected')}
        </p>
      ) : null}
      {token === '' ? null : (
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
          <p className="text-muted text-sm">{t('setPassword.intro')}</p>
          <FormField
            label={t('login.email')}
            type="email"
            autoComplete="username"
            error={form.formState.errors.email?.message}
            {...form.register('email')}
          />
          <FormField
            label={t('setPassword.newPassword')}
            type="password"
            autoComplete="new-password"
            hint={t('setPassword.rules')}
            error={form.formState.errors.password?.message}
            {...form.register('password')}
          />
          <FormField
            label={t('setPassword.repeat')}
            type="password"
            autoComplete="new-password"
            error={form.formState.errors.repeat?.message}
            {...form.register('repeat')}
          />
          <button
            type="submit"
            disabled={mutation.isPending}
            className="bg-primary text-on-primary min-h-12 rounded-xl font-semibold disabled:opacity-60"
          >
            {t('setPassword.submit')}
          </button>
        </form>
      )}
    </PublicPage>
  )
}
