import { useTranslation } from 'react-i18next'
import { useRegisterSW } from 'virtual:pwa-register/react'

import '../i18n/i18n.ts'

// ADR 0024: a new version waits until the user chooses "Reload".
// The browser downloads the new service worker in the background.
// "Later": the new version starts the next time the user opens the app.
export function UpdatePrompt() {
  const { t } = useTranslation()
  const {
    needRefresh: [needRefresh, setNeedRefresh],
    updateServiceWorker,
  } = useRegisterSW()

  if (!needRefresh) return null

  return (
    <div
      role="status"
      className="bg-ink text-ground fixed inset-x-3 bottom-24 z-20 flex flex-col gap-3 rounded-2xl p-4 shadow-lg lg:inset-x-auto lg:right-6 lg:bottom-6 lg:w-90"
    >
      <div>
        <p className="font-semibold">{t('update.title')}</p>
        <p className="text-sm opacity-80">{t('update.body')}</p>
      </div>
      <div className="flex justify-end gap-2">
        <button
          type="button"
          onClick={() => {
            setNeedRefresh(false)
          }}
          className="min-h-11 rounded-xl border border-current/30 px-4 font-semibold"
        >
          {t('update.later')}
        </button>
        <button
          type="button"
          onClick={() => {
            void updateServiceWorker(true)
          }}
          className="bg-primary-bright text-on-bright min-h-11 rounded-xl px-4 font-semibold"
        >
          {t('update.reload')}
        </button>
      </div>
    </div>
  )
}
