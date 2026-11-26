import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import type { TFunction } from 'i18next'
import { useTranslation } from 'react-i18next'
import { Link, Navigate, useSearchParams } from 'react-router'
import { z } from 'zod'

import { ApiError } from '../api/client.ts'
import { login } from './api.ts'
import { FormField } from './FormField.tsx'
import { PublicPage } from './PublicPage.tsx'
import { meQueryKey, safeNextPath, useMe } from './session.ts'

// docs/product/login.md. The server gives one general message for each failure.
export function LoginPage() {
  const { t } = useTranslation()
  const me = useMe()
  const queryClient = useQueryClient()
  const [searchParams] = useSearchParams()
  const next = safeNextPath(searchParams.get('next'))

  const schema = z.object({
    email: z.string().trim().min(1, t('login.enterEmail')),
    password: z.string().min(1, t('login.enterPassword')),
  })
  type Values = z.infer<typeof schema>

  const form = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '' },
  })

  const mutation = useMutation({
    mutationFn: (values: Values) => login(values.email, values.password),
    // The saved user makes the page send the user on to "next" (see below).
    onSuccess: (user) => {
      queryClient.setQueryData(meQueryKey, user)
    },
  })

  if (me.isPending) return null
  // Logged in: after a login, or when a logged-in user opens /login.
  if (me.data != null) return <Navigate to={next} replace />

  return (
    <PublicPage title={t('login.title')}>
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
          label={t('login.email')}
          type="email"
          autoComplete="username"
          error={form.formState.errors.email?.message}
          {...form.register('email')}
        />
        <FormField
          label={t('login.password')}
          type="password"
          autoComplete="current-password"
          error={form.formState.errors.password?.message}
          {...form.register('password')}
        />
        {mutation.isError ? (
          <p
            role="alert"
            className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
          >
            {loginErrorText(mutation.error, t)}
          </p>
        ) : null}
        <button
          type="submit"
          disabled={mutation.isPending}
          className="bg-primary text-on-primary min-h-12 rounded-xl font-semibold disabled:opacity-60"
        >
          {t('login.submit')}
        </button>
      </form>
      <Link
        to="/forgot-password"
        className="text-primary flex min-h-11 items-center justify-center text-sm font-semibold"
      >
        {t('forgot.title')}
      </Link>
    </PublicPage>
  )
}

function loginErrorText(error: Error, t: TFunction): string {
  if (error instanceof ApiError) {
    if (error.status === 429) return t('login.tooMany')
    const message = error.fieldError('email')
    if (message !== undefined) return message
  }
  return t('common.failed')
}
