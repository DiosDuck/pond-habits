# CI checks

The PR checks from [ADR 0012](adr/0012-ci-cd-pipeline.md). Each job below is a required status check on `main`.
Each job runs the same `make` target as on the developer's machine ([ADR 0006](adr/0006-docker-only-development.md)).

## Workflow "PR checks" (`.github/workflows/pr.yml`)

| Job | Local command | What it checks |
|---|---|---|
| `repo-checks` | `make selftest lint-repo` | Hook and script self-tests, Markdown, links, sensitive data, scenarios, action pins, workflow lint |
| `secrets-scan` | `make secrets-scan` | gitleaks on all commits |
| `lint-backend` | `make lint-backend` | Composer, Pint, PHPStan, Rector, unused and missing packages |
| `lint-frontend` | `make lint-frontend` | TypeScript, ESLint, Prettier, knip, dependency-cruiser |
| `test-backend` | `make test-backend` | Pest unit, integration (PostgreSQL, Mailpit) and architecture tests, coverage |
| `test-frontend` | `make test-frontend` | Vitest component tests, coverage |
| `dependencies` | `make audit licence-check` | Security issues (high or critical) and licences |
| `e2e` | `make e2e` | Playwright on mobile viewports, axe |
| `lighthouse` | `make lighthouse` | Production build, bundle budget, PWA fields, Lighthouse scores |
| `image-build (amd64)`, `image-build (arm64)` | `make images` | The prod images build and answer GET requests (smoke test) |

## Workflow "PR rules" (`.github/workflows/pr-rules.yml`)

These jobs read the PR title, text and labels. They run again when the PR text or a label changes.

| Job | Local command | What it checks |
|---|---|---|
| `pr-rules` | `bash scripts/pr-check.sh` | Conventional Commit title, branch name, PR boxes, "Why this package" lines |
| `test-guard` | `make test-guard` | No change to an existing test without the label `tests-change-approved` |

## Workflow "Images" (`.github/workflows/images.yml`)

It runs on each merge to `main`. It is not a PR check.

| Image | Dockerfile | Contents |
|---|---|---|
| `ghcr.io/diosduck/pond-habits/api` | `backend/Dockerfile`, target `prod` | PHP-FPM with the Laravel app, no dev packages. Also for the worker and the scheduler. |
| `ghcr.io/diosduck/pond-habits/web` | `infra/caddy/Dockerfile` | Caddy with the frontend build and a copy of `backend/public/` |

Each image has the tags `main` (the newest build) and `sha-<7 characters>` (one commit).
Each tag holds an amd64 and an arm64 image. Each architecture builds on its own native runner.

## Workflows "Renovate" and "Release"

Both use a token from the project's own GitHub App. See [CI bot](ci-bot.md).

- `renovate.yml`: weekly, and by hand. Grouped PRs for minor and patch updates (config: `.github/renovate.json5`).
  A major update, or a new PHP, Node or PostgreSQL version, waits for approval on the Dependency Dashboard issue.
- `release.yml`: on each merge to `main`. release-please keeps the release PR up to date.
  Its merge makes the tag `v<version>`. Do not merge it before the first deploy (Phase 4).

## Workflow "Nightly" (`.github/workflows/nightly.yml`)

Each night, and by hand. GitHub sends an email to the owner when a run fails.

| Job | Local command | What it checks |
|---|---|---|
| `mutation-backend` | `make mutation-backend` | Pest mutation score 60% or more |
| `mutation-frontend` | `make mutation-frontend` | StrykerJS mutation score 60% or more. The report is an artifact for 14 days. |
| `dependencies` | `make audit` | New security issues in packages that did not change |

## Deploy workflows

| Workflow | Job | When | What it does |
|---|---|---|---|
| `deploy-staging.yml` | `deploy-staging` | After a green "Images" run on `main` | Deploy to staging ([infra/deploy/README.md](../infra/deploy/README.md)) |
| `deploy-staging.yml` | `zap-baseline` | After `deploy-staging` | `make zap-baseline`: OWASP ZAP passive scan of staging with the staging login. Fails on a high-risk alert. |
| `deploy-production.yml` | `deploy-production` | On a tag `v*`, after the owner's approval | Deploy to production, with a database dump first |

The ZAP job prints a list of alerts (risk, rule ID, name) in the run summary. It uploads no report, because the Actions artifacts of a public repository are public. For the details, run `make zap-baseline URL=...` on your machine. A reviewed rule that must not fail the scan goes in `.zap/ignore.tsv`, with the reason.

## Required checks on `main`

Set these in the branch rules of `main`: `repo-checks`, `secrets-scan`, `lint-backend`, `lint-frontend`,
`test-backend`, `test-frontend`, `dependencies`, `e2e`, `lighthouse`, `image-build (amd64)`,
`image-build (arm64)`, `pr-rules`, `test-guard`.
When you add or rename a PR job, update this list and the branch rules together.

## The PR boxes

The two boxes in the "Checks" section of the PR template must agree with the diff:

- "changes a recorded decision": check it when the diff changes `docs/adr/` or `docs/privacy.md`.
- "changes `scripts/` or `.claude/`": check it when the diff changes these folders.

## When a check fails

1. Open the failed job on the PR page. Copy the job name and the error lines.
2. Run the local command from the tables above.
3. The E2E job keeps the Playwright report as an artifact for 7 days. The Lighthouse job keeps its report too.

## Rules for workflow files

- Pin each action to a full commit SHA. Write the version in a comment: `uses: owner/repo@<sha> # v1.2.3`.
- Give the token only the permissions that the job needs. The default is `contents: read`.
- Do not put PR text into a `run:` script with `${{ }}`. Give it to the script in an `env:` variable.
- Use `actions/checkout` with `persist-credentials: false`.
