import { useTranslation } from 'react-i18next'
import { NavLink } from 'react-router'

import { Icon, type IconName } from '../ui/Icon.tsx'

interface Tab {
  to: string
  label: string
  icon: IconName
  // The filled form marks the active tab (ADR 0024). Some symbols have one form only.
  activeIcon: IconName
}

interface MainNavProps {
  // Today has no tab bar on a phone: its cards are the menu.
  hideOnPhone: boolean
}

// One navigation for all screen sizes: a bottom tab bar on a phone,
// a left sidebar at 1024 px and wider (Tailwind "lg").
export function MainNav({ hideOnPhone }: MainNavProps) {
  const { t } = useTranslation()
  const tabs: Tab[] = [
    { to: '/', label: t('nav.today'), icon: 'home', activeIcon: 'home-fill' },
    {
      to: '/focus',
      label: t('nav.focus'),
      icon: 'timer',
      activeIcon: 'timer-fill',
    },
    {
      to: '/tasks',
      label: t('nav.tasks'),
      icon: 'task_alt',
      activeIcon: 'task_alt',
    },
    {
      to: '/habits',
      label: t('nav.habits'),
      icon: 'event_repeat',
      activeIcon: 'event_repeat',
    },
    {
      to: '/more',
      label: t('nav.more'),
      icon: 'more_horiz',
      activeIcon: 'more_horiz',
    },
  ]

  return (
    <nav
      aria-label={t('nav.label')}
      className={`${hideOnPhone ? 'hidden' : 'grid'} border-line bg-card fixed inset-x-0 bottom-0 z-10 grid-cols-5 border-t px-1 pt-1.5 pb-[max(0.375rem,env(safe-area-inset-bottom))] lg:sticky lg:top-0 lg:flex lg:h-dvh lg:w-58 lg:flex-none lg:flex-col lg:gap-1 lg:border-t-0 lg:border-r lg:px-3 lg:py-4`}
    >
      {tabs.map((tab) => (
        <NavLink
          key={tab.to}
          to={tab.to}
          end={tab.to === '/'}
          className={({ isActive }) =>
            `flex min-h-13 flex-col items-center justify-center gap-0.5 rounded-xl text-xs lg:min-h-11 lg:flex-row lg:justify-start lg:gap-3 lg:px-3 lg:text-sm ${
              isActive
                ? 'text-primary lg:bg-primary-soft font-semibold'
                : 'text-muted hover:text-primary'
            }`
          }
        >
          {({ isActive }) => (
            <>
              <Icon name={isActive ? tab.activeIcon : tab.icon} />
              <span>{tab.label}</span>
            </>
          )}
        </NavLink>
      ))}
    </nav>
  )
}
