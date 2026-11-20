# ADR 0007: Repository and git rules

Status: Accepted (2026-11-20)

## Context

GitHub Free gives branch protection, rulesets and environment approval only to public repos.

## Decision

- One public monorepo on GitHub: `backend/`, `frontend/`, `infra/`, `docs/`.
- Licence: MIT.
- `main` is protected. Changes go in only by PR, with all required checks green.
- Force push is blocked on all branches (local hook and Claude hook) and on `main` (GitHub).
- Squash merge only. Commit messages follow Conventional Commits.
- Short-lived branches: `feat/`, `fix/`, `chore/`, `docs/`.

## Hooks

| Hook | Checks |
|---|---|
| pre-commit | Format and lint of staged files, gitleaks, Markdown line limit. Less than 15 s. |
| commit-msg | Conventional Commits |
| pre-push | No force push, no push to `main`, branch name, `make check-fast` |

## Consequences

- The code is visible to all. Secrets stay in GitHub Secrets. gitleaks guards the repo.
- The PR test-guard uses a label, because the owner cannot approve the owner's own PR.
