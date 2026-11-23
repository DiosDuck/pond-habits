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

## Workflow "PR rules" (`.github/workflows/pr-rules.yml`)

These jobs read the PR title, text and labels. They run again when the PR text or a label changes.

| Job | Local command | What it checks |
|---|---|---|
| `pr-rules` | `bash scripts/pr-check.sh` | Conventional Commit title, branch name, PR boxes, "Why this package" lines |
| `test-guard` | `make test-guard` | No change to an existing test without the label `tests-change-approved` |

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
