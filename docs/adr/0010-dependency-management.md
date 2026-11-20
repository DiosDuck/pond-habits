# ADR 0010: Dependency management

Status: Accepted (2026-11-20)

## Decision

- Lockfiles are in the repo. CI installs with `composer install` and `npm ci`.
- Package manager for the frontend: npm (bundled with Node).
- `composer audit` and `npm audit` run in CI. They fail on high or critical issues.
- A licence check allows MIT, BSD, Apache-2.0, ISC and similar licences.
- A new package needs a "Why this package" line in the PR. A CI script checks it.
- GitHub Actions are pinned to a commit SHA.
- Renovate runs inside our own GitHub Actions, weekly, with grouped PRs. No third-party app gets access to the repo.
- Dependabot security alerts are on as a backup.
- Renovate opens Scramble updates (0.x) as separate PRs, because a 0.x minor can break.

## What Renovate and Dependabot do

They watch the dependencies (Composer, npm, Docker images, GitHub Actions). When a new version comes, they open a PR. CI tests the PR. The owner merges it when the checks are green. They do not use AI and they cost nothing.
