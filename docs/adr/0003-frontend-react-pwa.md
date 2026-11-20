# ADR 0003: Frontend: React PWA

Status: Accepted (2026-11-20)

## Context

The app is mobile browser first. The owner has basic Angular skill and wants to learn a new frontend stack. The owner must understand the frontend code.

## Decision

| Need | Choice |
|---|---|
| Framework | React 19 + Vite 8 + TypeScript (strict) |
| Routing | React Router 8 |
| Server data | TanStack Query 5 |
| Forms | React Hook Form 7 + Zod 4 |
| Styling | Tailwind CSS 4 |
| Base components | shadcn/ui (Radix primitives, code copied into the repo) |
| Global UI state | React context. Zustand only if necessary. |
| PWA | vite-plugin-pwa (Workbox) |

## Learning support

1. `docs/learning/react-for-angular-devs.md`: a map of Angular concepts to React concepts.
2. Each frontend PR has a "Concepts" section with links to react.dev.
3. The `/explain` skill explains a file or a PR.
4. The owner writes the Pomodoro feature with Claude as a coach.

## Consequences

- A native app can come later with Capacitor, on the same code.
- Storybook is in the backlog.
