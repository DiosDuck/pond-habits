import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'

import { Icon, type IconName } from '../ui/Icon.tsx'

// The home page (docs/product/today.md, ADR 0024). Each feature adds its card here.
// The cards have no data yet: the features come in later versions.
export function TodayPage() {
  const { t, i18n } = useTranslation()
  const date = new Intl.DateTimeFormat(i18n.language, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(new Date())

  return (
    <div className="mx-auto max-w-3xl">
      <header className="flex items-start justify-between gap-2 px-5 pt-5 pb-2">
        <div>
          <p className="text-muted text-sm">{date}</p>
          <h1 className="font-display text-3xl font-extrabold">
            {t('today.title')}
          </h1>
        </div>
        <div className="flex gap-1">
          <HeaderLink
            to="/admin"
            icon="shield_person"
            label={t('pages.admin')}
          />
          <HeaderLink
            to="/settings"
            icon="settings"
            label={t('pages.settings')}
          />
        </div>
      </header>
      <main className="grid grid-cols-2 gap-3 px-4 pb-6">
        <Card to="/rewards" wide>
          <Icon name="toll" size={32} className="text-primary flex-none" />
          <CardText title={t('today.credits')} hint={t('today.creditsHint')} />
        </Card>
        <Link
          to="/focus"
          className="bg-primary text-on-primary col-span-2 flex min-h-20 items-center justify-between gap-3 rounded-2xl px-4 py-4 hover:opacity-90"
        >
          <span className="flex flex-col">
            <span className="font-display text-xl font-extrabold">
              {t('today.startFocus')}
            </span>
            <span className="text-sm opacity-90">
              {t('today.startFocusHint')}
            </span>
          </span>
          <Icon name="timer" size={40} />
        </Link>
        <Card to="/tasks">
          <CardText title={t('nav.tasks')} hint={t('today.tasksHint')} />
        </Card>
        <Card to="/habits">
          <CardText title={t('nav.habits')} hint={t('today.habitsHint')} />
        </Card>
        <Card to="/nutrition" wide>
          <CardText
            title={t('pages.nutrition')}
            hint={t('today.nutritionHint')}
          />
        </Card>
      </main>
    </div>
  )
}

interface HeaderLinkProps {
  to: string
  icon: IconName
  label: string
}

function HeaderLink({ to, icon, label }: HeaderLinkProps) {
  return (
    <Link
      to={to}
      className="hover:text-primary flex min-h-13 w-16 flex-col items-center justify-center gap-0.5 rounded-xl text-xs"
    >
      <Icon name={icon} />
      <span>{label}</span>
    </Link>
  )
}

interface CardProps {
  to: string
  wide?: boolean
  children: ReactNode
}

function Card({ to, wide = false, children }: CardProps) {
  return (
    <Link
      to={to}
      className={`border-line bg-card hover:border-primary flex min-h-20 items-center gap-3 rounded-2xl border p-4 ${wide ? 'col-span-2' : ''}`}
    >
      {children}
    </Link>
  )
}

function CardText({ title, hint }: { title: string; hint: string }) {
  return (
    <span className="flex flex-col">
      <span className="font-semibold">{title}</span>
      <span className="text-muted text-sm">{hint}</span>
    </span>
  )
}
