import { useEffect, useId, useRef } from 'react'
import { useTranslation } from 'react-i18next'

interface ConfirmDisableDialogProps {
  name: string
  busy: boolean
  onConfirm: () => void
  onCancel: () => void
}

// The confirm before "Disable" (docs/product/admin.md): a sheet from the bottom.
// Focus goes to "Cancel", the safe choice. Escape closes the sheet.
export function ConfirmDisableDialog({
  name,
  busy,
  onConfirm,
  onCancel,
}: ConfirmDisableDialogProps) {
  const { t } = useTranslation()
  const titleId = useId()
  const cancelRef = useRef<HTMLButtonElement>(null)

  useEffect(() => {
    cancelRef.current?.focus()
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onCancel()
    }
    window.addEventListener('keydown', onKey)
    return () => {
      window.removeEventListener('keydown', onKey)
    }
  }, [onCancel])

  return (
    <div className="fixed inset-0 z-30 flex items-end justify-center bg-black/45 lg:items-center">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        className="bg-card flex w-full max-w-md flex-col gap-4 rounded-t-3xl p-5 pb-8 lg:rounded-3xl"
      >
        <h2 id={titleId} className="font-display text-xl font-extrabold">
          {t('admin.confirmTitle', { name })}
        </h2>
        <ul className="list-disc pl-5 text-sm leading-relaxed">
          <li>{t('admin.confirmLoggedOut', { name })}</li>
          <li>{t('admin.confirmNoLogin', { name })}</li>
          <li>{t('admin.confirmData')}</li>
        </ul>
        <div className="flex gap-3">
          <button
            ref={cancelRef}
            type="button"
            onClick={onCancel}
            className="border-line min-h-12 flex-1 rounded-xl border font-semibold"
          >
            {t('admin.cancel')}
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={busy}
            className="bg-minus min-h-12 flex-1 rounded-xl font-semibold text-white disabled:opacity-60"
          >
            {t('admin.disable')}
          </button>
        </div>
      </div>
    </div>
  )
}
