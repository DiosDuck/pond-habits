# ADR 0017: Version pins

Status: Accepted (2026-11-20). Versions checked on 2026-11-20.

| Item | Pin | Note |
|---|---|---|
| PHP | 8.5 | 8.6 is too new for Laravel 13 and its tools. 8.4 active support ends 2026-12-31. |
| Laravel | 13.x | Security support to 2028-03-17. Laravel 14 is expected about Q1 2027. |
| Sanctum | 4.x | |
| Pest | 5.x | Needs PHP 8.4 or later. `--mutate` is stable. |
| PHPStan / Larastan | 2.x / 3.x | |
| Scramble | 0.13.x | Pre-1.0. Pin the minor. |
| PostgreSQL | 18 | 19.0 is too new. 18 EOL is 2030-11. |
| Node | 26 LTS (npm 11) | Active LTS to 2027-10. |
| React | 19.3 | |
| Vite / Vitest / vite-plugin-pwa | 8 / 5 / 2 | |
| TypeScript | 6.0.x | Change to 7 (Go compiler) when typescript-eslint and the other tools support it. |
| React Router / TanStack Query | 8 / 5 | |
| React Hook Form / Zod | 7 / 4 | RHF 8 is still beta. |
| Tailwind CSS | 4.3 | |
| Playwright / StrykerJS | 1.65 / 10 | |
| Caddy | 2.11 | |

## Rules

- All Docker images are multi-arch (amd64 and arm64). This was checked on Docker Hub.
- Renovate handles minor and patch updates.
- A major upgrade needs an ADR and its own PR.
- Planned upgrades: Laravel 14, TypeScript 7.
