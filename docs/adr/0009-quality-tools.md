# ADR 0009: Quality tools

Status: Accepted (2026-11-20)

## Decision

| Purpose | Backend | Frontend |
|---|---|---|
| Format | Pint | Prettier |
| Static analysis | Larastan (PHPStan) level max, phpstan-strict-rules, phpstan-deprecation-rules | `tsc --strict` with `noUncheckedIndexedAccess`, typescript-eslint `strictTypeChecked` |
| Refactor checks | Rector (check mode only) | - |
| Dead code and unused packages | composer-unused, composer-require-checker | Knip |
| Architecture rules | Pest `arch()` tests | dependency-cruiser |
| Tests | Pest | Vitest, React Testing Library, Playwright |
| Mutation | Pest `--mutate` | StrykerJS |
| Accessibility | - | axe-core in Playwright |
| Performance | - | Lighthouse CI, bundle size limit |
| Secrets | gitleaks | gitleaks |

## Lighthouse budget (mobile profile)

Performance 90 or more. Accessibility 95 or more. Best Practices 95 or more. The PWA install checks pass. Initial JS bundle 200 KB gzip or less.

## Consequences

- All tools are free and run in containers.
- `make check` runs all of them. CI runs the same target in parallel jobs.
