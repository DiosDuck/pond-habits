# Accounts, admin and settings

## First admin

- The command `make admin` runs `php artisan app:create-admin <email>` in the API container.
- On the server, the owner runs it once through SSH on the tailnet, or through the manual admin workflow (see [ADR 0012](../adr/0012-ci-cd-pipeline.md)).
- The command sends a "set your password" link. No admin password is kept in an env file.
- Login, logout and the set-password flow: [login.md](login.md).

## Invites and registration

1. An admin sends an invite by email.
2. The invitee opens the link and completes the registration form.
3. The invite link counts as email verification ([ADR 0025](../adr/0025-invite-verifies-email.md)). The user is logged in at once.
4. Password reset is available: [password-reset.md](password-reset.md).

Details and scenarios: [invites.md](invites.md).

## Passwords

- Minimum 12 characters. No forced complexity rules.
- The app rejects known breached passwords (`Password::uncompromised()`).
- 2FA (TOTP) is in the backlog.

## Admin page

The admin page is inside the React app. The API rejects admin endpoints for non-admins (server-side authorization). Scenario tests `SCN-AUTH-ADMIN-*` check this. An admin can:

- send and cancel invites,
- see the user list,
- disable and enable a user.

Details and scenarios: [admin.md](admin.md). The open sign-up switch comes later, together with the public sign-up page (backlog).

## Settings page

Profile, time zone, theme, password, data export (JSON), account deletion. Details and scenarios: [settings.md](settings.md). Log out is on the More page. Reminder switches come in Phase 5.

## Demo users (local and staging only)

| User | Scenario |
|---|---|
| `admin@demo` | Admin, open invites (`friend@demo`, `neighbour@demo`), one disabled user (`disabled@demo`) |
| `new@demo` | No data (empty states) |
| `power@demo` | 6 months of history, long streaks, many rewards bought |
| `debt@demo` | Negative balance, missed recurring tasks |
| `relapse@demo` | Broken abstain habit, best streak is not current streak |
| `tz@demo` | Time zone `Pacific/Auckland` |
| `pomodoro@demo` | Break overruns, skips, abandoned focus sessions |
| `nutrition@demo` | Goals over and under limits, ranges |

- Local: the password is `password`.
- Staging: each deploy sets random passwords from a GitHub secret. The owner keeps them in the password manager.
- Production: no demo users. Demo seeders refuse to run when `APP_ENV=production`. A test checks this.

E2E tests use the same seed data (`make e2e` runs all demo seeders). Add more users when new scenarios come.

Each demo user comes with the phase of its data. Now: `admin@demo`, `disabled@demo`, `new@demo`.
Later: `pomodoro@demo` (focus), `power@demo` and `debt@demo` (credits, rewards, tasks),
`tz@demo` (tasks with due dates), `relapse@demo` (habits), `nutrition@demo` (nutrition).
