# ADR 0020: Name and domain

Status: Accepted (2026-11-20)

## Decision

- Name: Pond Habits (ducks live in a pond). The duck is the mascot.
- Code slug: `pond-habits` (repo, Docker images).
- Domain: `pondhabits.com`. Buy it before the repo is public. Do a trademark check first (EUIPO TMview).

| Address | Use |
|---|---|
| `pondhabits.com` | Production frontend |
| `api.pondhabits.com` | Production API |
| `staging.pondhabits.com` | Staging frontend (basic auth) |
| `api.staging.pondhabits.com` | Staging API (basic auth) |
| `status.pondhabits.com` | Uptime status page (optional) |

## Consequences

- `.com` does not force HTTPS. Caddy sends HSTS. HSTS preload is possible later.
