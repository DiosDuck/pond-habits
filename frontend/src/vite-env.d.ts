/// <reference types="vite-plugin-pwa/react" />

interface ImportMetaEnv {
  // The browser Sentry DSN (ADR 0014). Set at the image build; empty means "Sentry off".
  readonly VITE_SENTRY_DSN?: string
}
