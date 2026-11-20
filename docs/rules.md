# Rules and enforcement

Each rule has an enforcement type. Try to have as few "review" rules as possible.

- **hook**: a local git hook or a Claude Code hook stops the action.
- **CI**: a required GitHub Actions check fails.
- **GitHub**: a repository setting (branch protection) stops the action.
- **deploy**: the deploy workflow does the step.
- **review**: only a human review checks the rule.

## Git and repository

| Rule | Enforcement |
|---|---|
| Do not force push to any branch. | hook + GitHub |
| Do not push directly to `main`. Use a pull request. | hook + GitHub |
| All required checks must pass before a merge. | GitHub |
| Use squash merge only. | GitHub |
| Commit messages follow Conventional Commits. | hook (commit-msg) + CI |
| Branch names start with `feat/`, `fix/`, `chore/` or `docs/`. | hook (pre-push) + CI |
| Do not commit secrets. | hook (gitleaks) + CI |
| Do not commit personal or workstation data. See ADR 0023. | hook + CI (sensitive-pattern check) + Claude rule |
| If a secret leaks, rotate it. Do not rewrite history. | review |
| Only the owner pushes. Claude does not run `git push`. | Claude hook |
| No GitHub CLI or API scripts. GitHub admin is done in the web UI. | review |

## Tests

| Rule | Enforcement |
|---|---|
| Do not edit or delete an existing test file. Add new tests only. | Claude hook + CI (`test-guard`) |
| A change to an existing test needs the label `tests-change-approved`. Only the owner adds it. | CI |
| Write the tests first. The owner approves them before the implementation starts. | review + `/feature` skill |
| Each documented scenario (`SCN-*`) has a test with the same ID and event list. | CI (`docs-check`) |
| Coverage is 80% or more. | CI |
| The mutation score is 60% or more (nightly). | CI (nightly) |
| Claude runs `make check` before it reports that work is done. | Claude Stop hook |

## Code quality

| Rule | Enforcement |
|---|---|
| Format, lint and static analysis pass (see ADR 0009). | hook (staged files) + CI |
| The OpenAPI spec and the TypeScript API types agree with the code. | CI |
| The PWA meets the Lighthouse and bundle size budget. | CI |
| Accessibility: no axe violations (WCAG 2.2 AA). | CI (E2E) |

## Dependencies

| Rule | Enforcement |
|---|---|
| `composer audit` and `npm audit` have no high or critical issues. | CI |
| Licences are on the allow-list (MIT, BSD, Apache-2.0, ISC and similar). | CI |
| A new dependency has a "Why this package" line in the PR. | CI |
| GitHub Actions are pinned to a commit SHA. | CI |
| A major version upgrade has an ADR and its own PR. | review |

## Database and deploy

| Rule | Enforcement |
|---|---|
| A destructive migration (`drop`, `rename`) needs a label. | CI |
| Migrations are backward compatible (expand, then contract). | CI (label) + review |
| A backup runs before each production migration. | deploy |
| Production smoke tests are read-only (`GET` only). | CI (script check) |
| Production deploys only from a tag, after owner approval. | GitHub |
| Staging and production are always isolated. | Ansible + review |
| Staging is behind basic auth and uses random demo passwords. | deploy |
| SSH is only through Tailscale. | Ansible (firewall) |
| Invite and reset mails have strict rate limits. A global daily mail cap applies. | CI (test) |
| Demo seeders never run in production. | CI (test) + deploy |
| Admin endpoints check the role on the server. | CI (scenario tests) |

## Documents and decisions

| Rule | Enforcement |
|---|---|
| Markdown files have 100 lines or less. CI fails at more than 150. | hook + CI |
| All relative Markdown links resolve. | CI |
| A change to a recorded decision or the privacy baseline has an ADR. | CI (PR checkbox) |
| No personal data in logs. Use the user ID only. | CI (log processor test) |
| No AI step runs in CI. CI is deterministic. | review |
| No paid tool is added without an ADR. | review |
