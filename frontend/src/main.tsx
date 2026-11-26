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

const root = document.getElementById('root')
if (root === null) {
  throw new Error('The #root element is missing in index.html.')
}

createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
