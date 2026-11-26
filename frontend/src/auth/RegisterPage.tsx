import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { useNavigate, useSearchParams } from 'react-router'
import { z } from 'zod'

import { ApiError } from '../api/client.ts'
import { checkInvite, register } from './api.ts'
import { FormField } from './FormField.tsx'
import { PublicPage } from './PublicPage.tsx'
import { meQueryKey } from './session.ts'

// The page of the invite link (docs/product/invites.md, ADR 0025).
// The address comes from the invite: it is not an input.
export function RegisterPage() {
  const { t } = useTranslation()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') ?? ''
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  const invite = useQuery({
    queryKey: ['invite', token],
    queryFn: () => checkInvite(token),
    enabled: token !== '',
    retry: false,
  })

  const schema = z
    .object({
      name: z.string().trim().min(1, t('register.enterName')),
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
    defaultValues: { name: '', password: '', repeat: '' },
  })

  const mutation = useMutation({
    mutationFn: (values: Values) =>
      register({ token, name: values.name, password: values.password }),
    onSuccess: async (user) => {
      queryClient.setQueryData(meQueryKey, user)
      await navigate('/', { replace: true })
    },
    onError: (error) => {
      if (!(error instanceof ApiError)) return
      for (const field of ['name', 'password'] as const) {
        const message = error.fieldError(field)
        if (message !== undefined) form.setError(field, { message })
      }
    },
  })

  const rejected =
    token === '' ||
    invite.isError ||
    (mutation.error instanceof ApiError &&
      mutation.error.fieldError('token') !== undefined)

  return (
    <PublicPage title={t('register.title')}>
      {rejected ? (
        <p
          role="alert"
          className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
        >
          {t('register.inviteRejected')}
        </p>
      ) : null}
      {invite.data === undefined || rejected ? null : (
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
          <div className="flex flex-col gap-1">
            <span className="text-muted text-sm">
              {t('register.yourEmail')}
            </span>
            <span className="font-semibold break-all">{invite.data}</span>
          </div>
          <FormField
            label={t('register.name')}
            autoComplete="name"
            error={form.formState.errors.name?.message}
            {...form.register('name')}
          />
          <FormField
            label={t('register.password')}
            type="password"
            autoComplete="new-password"
            hint={t('register.rules')}
            error={form.formState.errors.password?.message}
            {...form.register('password')}
          />
          <FormField
            label={t('register.repeat')}
            type="password"
            autoComplete="new-password"
            error={form.formState.errors.repeat?.message}
            {...form.register('repeat')}
          />
          {mutation.isError && !hasFieldError(mutation.error) ? (
            <p
              role="alert"
              className="text-minus border-minus rounded-xl border p-3 text-sm font-semibold"
            >
              {t('common.failed')}
            </p>
          ) : null}
          <button
            type="submit"
            disabled={mutation.isPending}
            className="bg-primary text-on-primary min-h-12 rounded-xl font-semibold disabled:opacity-60"
          >
            {t('register.submit')}
          </button>
        </form>
      )}
    </PublicPage>
  )
}

// True when the server named a field: the form shows the message there.
function hasFieldError(error: Error): boolean {
  return (
    error instanceof ApiError &&
    (error.fieldError('name') !== undefined ||
      error.fieldError('password') !== undefined)
  )
}
