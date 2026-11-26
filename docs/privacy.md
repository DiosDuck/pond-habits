# Privacy baseline

Pond Habits stores health-related personal data (nutrition, habits such as smoking). The GDPR applies. This file gives the baseline. Each deviation from the baseline must be in the deviations table, with an ADR.

## Baseline

1. A user can export all of the user's data as JSON.
2. A user can delete the account. The app deletes all of the user's data at once.
3. Backups that still contain deleted data expire within 6 months (backup retention).
4. Passwords are hashed (Laravel default). All traffic uses HTTPS.
5. Backups are encrypted on the server before upload (restic).
6. The app has no third-party analytics and no trackers.
7. Logs contain the user ID only. Logs never contain emails, food entries or habit names.
8. Application logs are kept for 14 days, then deleted.
9. Staging contains demo data only. Staging never contains real personal data. A Mailpit container catches all staging mail.
10. The backup restore test runs on the VPS. It never runs on a public GitHub runner.
11. Production smoke tests are read-only and never log in.
12. Each user sees only the user's own data. The app has no social features.
13. A pending invite stores the invited email address. The app deletes the invite when it is used, and a daily job deletes expired invites.

## External services that receive data

| Service | Data sent | Why |
|---|---|---|
| Have I Been Pwned | First 5 characters of the SHA-1 hash of a new password (k-anonymity) | Block known breached passwords |
| Mail provider (Resend or Brevo) | Recipient email, mail content | Invites, verification, password reset |
| Sentry | Error data, user ID, no email | Error tracking |
| Backblaze B2 | Encrypted backup files only | Off-site backup |
| Browser push service | Encrypted push message | Web Push notifications |
| Better Stack | Heartbeat pings, no user data | Job monitoring |
| UptimeRobot | Public page checks, no user data | Uptime monitoring |
| Tailscale | Device and connection metadata | Private SSH access |
| Grafana Cloud (later) | Logs with user ID only, metrics | Monitoring |

## Deviations

| Date | Deviation | Reason | ADR |
|---|---|---|---|
| (none yet) | | | |

## How to add a deviation

1. Write or update an ADR that explains the deviation.
2. Add a row to the deviations table.
3. Tick the "privacy baseline" checkbox in the PR template.
