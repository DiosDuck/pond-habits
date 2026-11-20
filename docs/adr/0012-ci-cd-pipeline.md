# ADR 0012: CI/CD pipeline and environments

Status: Accepted (2026-11-20)

## Environments

| Environment | Where | When it updates |
|---|---|---|
| local | Docker on the developer's machine | Always |
| CI | GitHub Actions, temporary | Each PR |
| staging | VPS, `staging.pondhabits.com` | Each merge to `main` |
| production | VPS, `pondhabits.com` | Each tag `v*`, after owner approval |

Staging is a second copy of the same app with demo data. The owner tests each change on a phone before the release.

## Staging protection

- Caddy basic auth protects all of staging. The credentials are a secret.
- Demo users get random passwords on each deploy. `password` is for local only.
- Staging sends `noindex` headers.
- A Mailpit container catches all staging mail.
- The staging API docs and the Mailpit UI are only on the tailnet.

## Isolation (always)

Staging and production have separate Docker networks, database users, databases, volumes and secrets. Staging has resource limits, so it cannot starve production.

## Pipeline

1. **On PR**: lint, static analysis, unit, integration, E2E, audits, licence check, test-guard, docs-check, sensitive-data check, ADR checkbox, Markdown checks, Lighthouse, image build (no push).
2. **On merge to `main`**: build multi-arch images, push to GHCR, deploy to staging, migrate, smoke test, ZAP baseline scan.
3. **Release**: release-please keeps a release PR. A merge creates the tag.
4. **On tag `v*`**: owner approval, backup, deploy to production, migrate, smoke test, automatic rollback on failure.
5. **Nightly**: mutation tests.

## Rules

- All PR jobs are required status checks on `main`. CI has no AI step.
- The deploy job joins the tailnet with an ephemeral Tailscale key (a secret). It leaves the tailnet when the job ends.
- Secrets are GitHub Environment secrets. The deploy writes them to the server with strict file permissions. The owner keeps a copy in a password manager.
- Admin commands on the server run through SSH on the tailnet, or a manual workflow that only the owner can start. The workflow requires the protected `production` environment. It never runs from `pull_request_target`.
- The production deploy never runs `db:seed`.
