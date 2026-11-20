---
name: feature
description: Build one feature with the project workflow - scenario docs, tests first, owner approval, implementation, make check, PR text with Concepts. Use when the owner asks to build or add a feature, or runs /feature.
---

# /feature

The workflow for a feature (ADR 0008, ADR 0019, docs/rules.md). Do the steps in order.
Do not skip a STOP: the owner must answer before you continue.

## 1. Understand and branch

- Read the feature in `docs/product/` and the related ADRs.
- Ask the owner about each open point. Do not guess product decisions.
- Make a branch from an up-to-date `main`: `git fetch origin && git switch -c feat/<short-name> origin/main`.

## 2. Scenarios

- Use the `/scenario` skill for each user-visible flow and each important error path.
- **STOP.** Show the scenarios (text and event lists). Wait for the owner to approve them.

## 3. Tests first

- Write the tests as **new** files: backend feature tests with `scenario(...)`, unit tests,
  frontend component tests (Vitest, MSW) and, for a full user journey, a Playwright test in `frontend/e2e/`.
- The tests must fail now for the right reason (the feature does not exist). Run them and show the failure.
- **STOP.** Show the tests to the owner. Explain what each test proves. Wait for approval.
- After approval, commit the tests alone: `test(<area>): add tests for <feature>`.
  From now on the guard hook protects them. If a test is wrong later, stop and ask the owner.

## 4. Implement

- Write the smallest code that makes the tests pass. Follow the existing structure.
- A new package needs a reason. Note it for the PR text ("Why these packages").
- A changed decision needs the `/adr` skill.
- After an API change, run `make api-spec`.
- Run `make check`. Fix every failure. Do not report "done" while it fails.
- Commit with Conventional Commits, for example `feat(pomodoro): add the focus timer`.

## 5. Hand over

- Do not push. Give the owner the push command: `! git push -u origin <branch>`.
- Write the PR title and body in chat with the sections of `.github/pull_request_template.md`:
  Summary, Why these packages, Concepts (with react.dev or laravel.com links), Test, Checks.
- Tick the ADR checkbox only when the PR changes a recorded decision or the privacy baseline.
- Tick the `scripts/` or `.claude/` checkbox when the PR changes those folders.

## After the merge (owner)

The owner tests on staging with a phone, adds the feature card to the Today page, and releases
with release-please.
