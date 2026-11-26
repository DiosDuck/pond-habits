import 'i18next'

import type en from './en.json'

// Typed keys: t('a.wrong.key') is a compile error, not a raw key on the screen.
declare module 'i18next' {
  interface CustomTypeOptions {
    resources: { translation: typeof en }
  }
}
