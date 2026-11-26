import { Outlet, useMatch } from 'react-router'

import { MainNav } from './MainNav.tsx'

// The frame around each page: the main navigation and the page (ADR 0024).
export function AppLayout() {
  const isToday = useMatch({ path: '/', end: true }) !== null

  return (
    <div className="lg:flex">
      <MainNav hideOnPhone={isToday} />
      {/* Space at the bottom, so the tab bar does not cover the end of the page. */}
      <div className={`min-w-0 flex-1 ${isToday ? '' : 'pb-24'} lg:pb-0`}>
        <Outlet />
      </div>
    </div>
  )
}
