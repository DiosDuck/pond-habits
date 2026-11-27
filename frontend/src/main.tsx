import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'

// Self-hosted fonts (ADR 0024): the browser loads them from our server, not from Google.
import '@fontsource/inter/400.css'
import '@fontsource/inter/500.css'
import '@fontsource/inter/600.css'
import '@fontsource/nunito/700.css'
import '@fontsource/nunito/800.css'

import { App } from './app/App.tsx'
import './index.css'
import { sentryOptions } from './monitoring/sentry.ts'
import { startTheme } from './theme/theme.ts'

// The saved theme before the first render, so the page does not flash in the wrong colours.
startTheme()

// Error reports (ADR 0014): only on the real hosts and only with a DSN. The SDK loads as a
// separate file, so the main bundle stays small.
const sentry = sentryOptions(
  window.location.hostname,
  import.meta.env.VITE_SENTRY_DSN,
)
if (sentry !== undefined) {
  void import('@sentry/react').then((Sentry) => Sentry.init(sentry))
}

const root = document.getElementById('root')
if (root === null) {
  throw new Error('The #root element is missing in index.html.')
}

createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
