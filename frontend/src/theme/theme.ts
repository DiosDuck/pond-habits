// The theme (docs/product/settings.md). "system" follows the device (prefers-color-scheme).
// "light" and "dark" set data-theme on <html>; index.css has the colours for both.
// The device keeps a copy, so the page has the right colours before /api/me answers.

export type Theme = 'system' | 'light' | 'dark'

const storageKey = 'theme'

function isTheme(value: unknown): value is Theme {
  return value === 'system' || value === 'light' || value === 'dark'
}

export function applyTheme(theme: Theme): void {
  const root = document.documentElement
  if (theme === 'system') root.removeAttribute('data-theme')
  else root.dataset['theme'] = theme
  try {
    localStorage.setItem(storageKey, theme)
  } catch {
    // Private mode or blocked storage: the theme still works for this page view.
  }
}

// At start: use the copy on the device. A wrong value is ignored.
export function startTheme(): void {
  let saved: string | null
  try {
    saved = localStorage.getItem(storageKey)
  } catch {
    return
  }
  if (isTheme(saved)) applyTheme(saved)
}
