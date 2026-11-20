# ADR 0014: Monitoring and alerting

Status: Accepted (2026-11-20)

## Decision

| Layer | Tool | When |
|---|---|---|
| Uptime | UptimeRobot (external, free) | First deploy |
| Errors (backend and browser) | Sentry Developer (free) | First deploy |
| Scheduled jobs (backup, restore test) | Better Stack heartbeats (free) | First deploy |
| Logs | Structured JSON logs on the VPS, 14 days | First deploy |
| Logs, metrics and alert rules | Grafana Cloud free | Later |

## Alerts

- Alerts go by email only.
- The VPS never sends alerts itself. The external services send the alert emails from their own mail systems.
- Thus an alert still comes when the VPS is down or when the app mail quota is used up.
- Jobs work as a "dead man's switch": they ping a heartbeat after each run. A missing ping is an alert.
- Docker log rotation limits the log size for each container.

## Protection and minimal security alerts

- Sentry: spike protection, a rate limit on the client key, and inbound filters. A flood cannot use up the quota.
- Until Grafana comes, the app sends a Sentry warning event when failed logins pass a threshold in a time window, and when the mail cap is close. Sentry then sends an email.
