/// <reference types="vitest/config" />
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { realpathSync } from 'node:fs'
import { resolve } from 'node:path'
import { defineConfig, searchForWorkspaceRoot } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// Paths that the Laravel API serves (see infra/caddy/Caddyfile.local).
// The service worker must never answer them from the cache.
const apiPaths = [/^\/api\//, /^\/sanctum\//, /^\/docs\//, /^\/health$/]

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    VitePWA({
      // ADR 0024: the app asks the user before it loads a new version (UpdatePrompt).
      registerType: 'prompt',
      manifest: {
        // Do not change id, start_url or scope: each user must install the app again after a change.
        id: '/',
        scope: '/',
        name: 'Pond Habits',
        short_name: 'Pond Habits',
        description: 'Personal tracker for focus, tasks, habits and nutrition.',
        theme_color: '#0f766e',
        background_color: '#f4f8f7',
        display: 'standalone',
        start_url: '/',
        icons: [
          {
            src: '/icon.svg',
            sizes: 'any',
            type: 'image/svg+xml',
            purpose: 'any',
          },
        ],
      },
      workbox: {
        navigateFallbackDenylist: apiPaths,
      },
    }),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    // "caddy" is the host name that the E2E tests use inside the Docker network.
    allowedHosts: ['localhost', 'caddy'],
    // The browser opens the app through Caddy on port 8080 (one origin).
    hmr: { clientPort: 8080 },
    fs: {
      // StrykerJS runs the tests in a sandbox folder with a link to node_modules.
      // Vite follows the link and refuses files outside the sandbox (for example the "?raw" icons).
      // So also allow the real node_modules folder.
      allow: [
        searchForWorkspaceRoot(process.cwd()),
        realpathSync(resolve(process.cwd(), 'node_modules')),
      ],
    },
  },
  test: {
    environment: 'jsdom',
    // Only the component tests. The Playwright specs in e2e/ run with "make e2e".
    include: ['src/**/*.test.{ts,tsx}'],
    setupFiles: ['src/test/setup.ts'],
    restoreMocks: true,
    coverage: {
      provider: 'v8',
      include: ['src/**/*.{ts,tsx}'],
      exclude: ['src/main.tsx', 'src/test/**', 'src/**/*.test.{ts,tsx}'],
      thresholds: { lines: 80, branches: 80, functions: 80, statements: 80 },
    },
  },
})
