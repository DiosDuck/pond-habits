# Pond Habits: Documentation

Pond Habits is a personal tracker app. It helps a small group of users with daily focus, tasks, habits and nutrition. The user earns or loses virtual credits. The user spends credits on rewards that the user defines.

- Code slug: `pond-habits`
- Domain: `pondhabits.com`
- Licence: MIT. The repository is public.
- Status: No code exists yet.
- Decisions recorded on: 2026-11-20

## Read in this order

1. [Product scope](product/README.md): what the app does.
2. [Decisions (ADR)](adr/README.md): why the app is built this way.
3. [Rules](rules.md): the rules for all work, and how each rule is enforced.
4. [Privacy](privacy.md): the privacy baseline and its deviations.

## Summary of the stack

| Area | Choice |
|---|---|
| Backend | Laravel 13, PHP 8.5 (php-fpm), JSON API |
| Frontend | React 19, Vite 8, TypeScript, PWA, mobile browser first |
| Database | PostgreSQL 18. It also holds the queue and the cache. |
| Web server | Caddy 2 (TLS, reverse proxy, static files) |
| Development | Docker only. The dev setup needs only docker, git and make. |
| CI/CD | GitHub Actions, images on GHCR, deploy to a VPS |
| Hosting | One VPS (about 4 GB RAM) with staging and production |

## Rules for these documents

- Write in ASD-STE100 Simplified Technical English.
- Keep each file at 100 lines or less. CI fails a file with more than 150 lines.
- When a topic gets too large, make a folder with a `README.md` index and sub-files.
- When a decision changes, update or add an ADR. Do not change history silently.
