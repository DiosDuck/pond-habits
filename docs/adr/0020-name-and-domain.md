# ADR 0020: Name and domain

Status: Accepted (2026-11-20). Changed in place on 2026-11-27 with the owner's approval: see "Change 2026-11-27".

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

## Change 2026-11-27

- `api.pondhabits.com` and `api.staging.pondhabits.com` are removed (DNS records and edge Caddy). The app uses one origin: the API is at `/api` on the same host as the frontend ([ADR 0005](0005-auth-and-registration.md), Sanctum SPA cookie mode).
- `www.pondhabits.com` is added. Caddy redirects it to `pondhabits.com`. See [ADR 0026](0026-dns-without-proxy.md).

## Consequences

- `.com` does not force HTTPS. Caddy sends HSTS. HSTS preload is possible later.
