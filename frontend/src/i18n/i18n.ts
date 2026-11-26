import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'

import en from './en.json'

// All UI texts come from i18n keys (ADR 0001). English is the only language now.
// The import of this file starts i18n. It loads synchronously, so the first render has the texts.
void i18n.use(initReactI18next).init({
  lng: 'en',
  fallbackLng: 'en',
  resources: { en: { translation: en } },
  // React escapes the texts already.
  interpolation: { escapeValue: false },
  initAsync: false,
})
