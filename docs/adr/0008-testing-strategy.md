# ADR 0008: Testing strategy

Status: Accepted (2026-11-20)

## Context

Claude writes most of the code. Tests must prove that the app works as documented. Tests must not change to make a build pass.

## Decision

| Layer | Tool | Runs against | When |
|---|---|---|---|
| Unit | Pest | No DB | hook + PR |
| Integration (backend) | Pest | Real PostgreSQL + Mailpit | PR |
| Contract | Scramble + openapi-typescript | Generated files | PR |
| Component | Vitest + React Testing Library + MSW | jsdom | hook + PR |
| End-to-end | Playwright (mobile viewports) + axe | Full Docker stack | PR |
| Time-based | Pest frozen clock, Playwright clock | Same as above | PR |
| Smoke | Script, `GET` only | Staging, production | Deploy |
| Mutation | Pest `--mutate`, StrykerJS | Code | Nightly |

## Scenario documents and event logs

- The app writes structured domain events to the log, for example `pomodoro.completed`.
- Each documented flow has a scenario ID (`SCN-POMO-001`) and an expected event list.
- An integration or E2E test with the same ID checks the logged event sequence.
- `docs-check` (CI) fails if an ID has no test, a test has no ID, or the event lists differ.

## Test protection

1. A Claude hook blocks edits and deletes of existing test files.
2. The CI job `test-guard` fails on changed or deleted tests without the label `tests-change-approved`.
3. Tests come first. The owner approves them before the implementation.

## Gates

Coverage 80% or more. Mutation score 60% or more. Raise the gates later.
