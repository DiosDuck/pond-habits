import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type ReactNode } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { z } from 'zod'

import { ApiError } from '../api/client.ts'
import { FormField } from '../auth/FormField.tsx'
import { meQueryKey } from '../auth/session.ts'
import { PageHeader } from '../layout/PageHeader.tsx'
import { applyTheme, type Theme } from '../theme/theme.ts'
import {
  changePassword,
  downloadMyData,
  getSettings,
  updateSettings,
  type Settings,
} from './api.ts'
import { DeleteAccountDialog } from './DeleteAccountDialog.tsx'

const settingsKey = ['settings'] as const

// The Settings page (docs/product/settings.md). Design: row 6 of the design canvas.
export function SettingsPage() {
  const { t } = useTranslation()
  const settings = useQuery({ queryKey: settingsKey, queryFn: getSettings })

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={t('pages.settings')} />
      <main className="flex flex-col gap-4 px-4 pb-6">
        {settings.data === undefined ? null : (
          <>
            <ProfileSection settings={settings.data} />
            <ThemeSection current={settings.data.theme} />
          </>
        )}
        <PasswordSection />
        <DataSection />
        <DeleteSection />
      </main>
    </div>
  )
}

function Section({
  title,
  danger = false,
  children,
}: {
  title: string
  danger?: boolean
  children: ReactNode
}) {
  return (
    <section
      className={`bg-card flex flex-col gap-3 rounded-2xl border p-4 ${danger ? 'border-minus/40' : 'border-line'}`}
    >
      <h2 className={`font-semibold ${danger ? 'text-minus' : ''}`}>{title}</h2>
      {children}
    </section>
  )
}

function useSaveSettings() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: updateSettings,
    onSuccess: (settings) => {
      queryClient.setQueryData(settingsKey, settings)
    },
  })
}

function ProfileSection({ settings }: { settings: Settings }) {
  const { t } = useTranslation()
  const deviceZone = Intl.DateTimeFormat().resolvedOptions().timeZone
  // All zones of the browser. The saved zone is in the list too, also when the browser lacks it.
  const zones = [
    ...new Set([
      settings.timezone,
      deviceZone,
      ...Intl.supportedValuesOf('timeZone'),
    ]),
  ].sort()

  const schema = z.object({
    name: z.string().trim().min(1, t('register.enterName')),
    timezone: z.string().min(1),
  })
  type Values = z.infer<typeof schema>
  const form = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { name: settings.name, timezone: settings.timezone },
  })
  const save = useSaveSettings()

  return (
    <Section title={t('settings.profile')}>
      <form
        noValidate
        onSubmit={(event) => {
          // handleSubmit returns a Promise. React does not wait for it.
          void form.handleSubmit((values) => {
            save.mutate(values)
          })(event)
        }}
        className="flex flex-col gap-3"
      >
        <FormField
          label={t('settings.name')}
          autoComplete="name"
          error={form.formState.errors.name?.message}
          {...form.register('name')}
        />
        <label className="flex flex-col gap-1.5 text-sm font-semibold">
          {t('settings.timezone')}
          <select
            className="border-line bg-ground min-h-11 rounded-xl border px-3 text-base font-normal"
            {...form.register('timezone')}
          >
            {zones.map((zone) => (
              <option key={zone} value={zone}>
                {zone}
              </option>
            ))}
          </select>
        </label>
        <button
          type="button"
          onClick={() => {
            form.setValue('timezone', deviceZone, { shouldDirty: true })
          }}
          className="text-primary border-line min-h-11 self-start rounded-xl border px-3 text-sm font-semibold"
        >
          {t('settings.useDevice', { zone: deviceZone })}
        </button>
        <p className="text-muted text-sm">{t('settings.dayStarts')}</p>
        {save.isSuccess ? (
          <p role="status" className="text-primary text-sm font-semibold">
            {t('settings.saved')}
          </p>
        ) : null}
        {save.isError ? (
          <p role="alert" className="text-minus text-sm font-semibold">
            {t('common.failed')}
          </p>
        ) : null}
        <button
          type="submit"
          disabled={save.isPending}
          className="bg-primary text-on-primary min-h-12 rounded-xl font-semibold disabled:opacity-60"
        >
          {t('settings.save')}
        </button>
      </form>
    </Section>
  )
}

function ThemeSection({ current }: { current: Theme }) {
  const { t } = useTranslation()
  const [theme, setTheme] = useState<Theme>(current)
  const save = useSaveSettings()
  const options: { value: Theme; label: string }[] = [
    { value: 'system', label: t('settings.themeSystem') },
    { value: 'light', label: t('settings.themeLight') },
    { value: 'dark', label: t('settings.themeDark') },
  ]

  return (
    <Section title={t('settings.theme')}>
      <div
        role="radiogroup"
        aria-label={t('settings.theme')}
        className="bg-ground grid grid-cols-3 gap-1.5 rounded-2xl p-1"
      >
        {options.map((option) => {
          const checked = option.value === theme
          return (
            <button
              key={option.value}
              type="button"
              role="radio"
              aria-checked={checked}
              onClick={() => {
                // The theme changes at once. The account keeps it for the other devices.
                setTheme(option.value)
                applyTheme(option.value)
                save.mutate({ theme: option.value })
              }}
              className={`min-h-11 rounded-xl text-sm font-semibold ${checked ? 'bg-card text-primary' : 'text-muted'}`}
            >
              {option.label}
            </button>
          )
        })}
      </div>
      <p className="text-muted text-sm">{t('settings.themeHint')}</p>
    </Section>
  )
}

function PasswordSection() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const schema = z
    .object({
      current: z.string().min(1, t('login.enterPassword')),
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
    defaultValues: { current: '', password: '', repeat: '' },
  })

  const mutation = useMutation({
    mutationFn: (values: Values) =>
      changePassword({ current: values.current, password: values.password }),
    // All sessions end, this device too: log in again with the new password.
    onSuccess: async () => {
      queryClient.setQueryData(meQueryKey, null)
      await navigate('/login', { replace: true })
    },
    onError: (error) => {
      if (!(error instanceof ApiError)) return
      const current = error.fieldError('current_password')
      if (current !== undefined) form.setError('current', { message: current })
      const password = error.fieldError('password')
      if (password !== undefined)
        form.setError('password', { message: password })
    },
  })

  return (
    <Section title={t('settings.password')}>
      <form
        noValidate
        onSubmit={(event) => {
          void form.handleSubmit((values) => {
            mutation.mutate(values)
          })(event)
        }}
        className="flex flex-col gap-3"
      >
        <FormField
          label={t('settings.currentPassword')}
          type="password"
          autoComplete="current-password"
          error={form.formState.errors.current?.message}
          {...form.register('current')}
        />
        <FormField
          label={t('settings.newPassword')}
          type="password"
          autoComplete="new-password"
          hint={t('register.rules')}
          error={form.formState.errors.password?.message}
          {...form.register('password')}
        />
        <FormField
          label={t('settings.repeat')}
          type="password"
          autoComplete="new-password"
          error={form.formState.errors.repeat?.message}
          {...form.register('repeat')}
        />
        <p className="text-muted text-sm">{t('settings.passwordHint')}</p>
        <button
          type="submit"
          disabled={mutation.isPending}
          className="bg-primary text-on-primary min-h-12 rounded-xl font-semibold disabled:opacity-60"
        >
          {t('settings.changePassword')}
        </button>
      </form>
    </Section>
  )
}

function DataSection() {
  const { t } = useTranslation()
  const download = useMutation({ mutationFn: downloadMyData })

  return (
    <Section title={t('settings.data')}>
      <p className="text-muted text-sm">{t('settings.dataHint')}</p>
      {download.isError ? (
        <p role="alert" className="text-minus text-sm font-semibold">
          {t('common.failed')}
        </p>
      ) : null}
      <button
        type="button"
        disabled={download.isPending}
        onClick={() => {
          download.mutate()
        }}
        className="border-line min-h-12 rounded-xl border font-semibold disabled:opacity-60"
      >
        {t('settings.download')}
      </button>
    </Section>
  )
}

function DeleteSection() {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  return (
    <Section title={t('settings.delete')} danger>
      <p className="text-muted text-sm">{t('settings.deleteHint')}</p>
      <button
        type="button"
        onClick={() => {
          setOpen(true)
        }}
        className="text-minus border-minus/40 min-h-12 rounded-xl border font-semibold"
      >
        {t('settings.deleteButton')}
      </button>
      {open ? (
        <DeleteAccountDialog
          onClose={() => {
            setOpen(false)
          }}
        />
      ) : null}
    </Section>
  )
}
