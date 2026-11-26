# Product scope

## Users

- About 10 users at most. Each user sees only the user's own data.
- Registration is by invite only. An admin setting can open public sign-up later.
- Two roles: `admin` and `user`.

## Platform

- Progressive Web App (PWA), mobile browser first. The user can install it on the home screen.
- A native app (Capacitor) is in the backlog only.
- Offline: the app shell and the pomodoro timer work offline. All data entry needs the network.

## Pages

| Page | File |
|---|---|
| Accounts, admin, settings | [accounts.md](accounts.md) |
| First admin, set password, login, logout | [login.md](login.md) |
| Invites and registration | [invites.md](invites.md) |
| Password reset | [password-reset.md](password-reset.md) |
| Today (home) | [today.md](today.md) |
| Pomodoro | [pomodoro.md](pomodoro.md) |
| Credits and rewards | [credits-rewards.md](credits-rewards.md) |
| Tasks | [tasks.md](tasks.md) |
| Habits | [habits.md](habits.md) |
| Nutrition | [nutrition.md](nutrition.md) |

## Global rules

- Each user has a time zone setting. The default comes from the browser.
- The day changes at 00:00 in the user's time zone. The database stores all times in UTC.
- The UI is in English. All texts use i18n keys, so translations can come later.
- Accessibility target: WCAG 2.2 AA. Touch targets are 44 px or more.
- Light and dark theme follow the system setting. A manual toggle is available.
- Browsers: the last 2 versions of iOS Safari, Chrome Android, and desktop Chrome, Firefox, Safari and Edge.
- Web Push reminders: the user can switch each reminder type on or off. On iPhone, push works only when the PWA is installed (iOS 16.4 or later).

## Open design items

[ADR 0024](../adr/0024-visual-design.md) records the palette, the navigation, the icon set and the mascot brief. Still open: the empty states and the first-use flow (design round 2), and the final mascot art.

## Scenario format

Each documented flow has a scenario ID and the expected list of domain events (ADR 0008). `make lint-repo` (`scripts/docs-check.sh`) compares the documents with the tests.

In a document in this folder:

```markdown
### SCN-POMO-001: Complete a focus session
Events: `pomodoro.started`, `pomodoro.completed`
```

In a test (PHP or TypeScript), on one line:

```php
scenario('SCN-POMO-001', ['pomodoro.started', 'pomodoro.completed']);
```
