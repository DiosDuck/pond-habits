# ADR 0015: Security baseline

Status: Accepted (2026-11-20)

## Decision

- Cookies are secure, HttpOnly and SameSite. CSRF protection is on.
- Login and sensitive endpoints are rate limited. Invite and reset mails have strict limits for each email and IP address.
- Rate-limit values are not published in the docs.
- Caddy sets security headers: HSTS, frame protection (`frame-ancestors`), `nosniff`, Referrer-Policy, Permissions-Policy.
- Caddy sets a strict Content-Security-Policy. Only our own domains and Sentry are allowed.
- OWASP ZAP baseline scan (passive) runs on staging after each deploy. It fails on high findings.
- `/health` returns only `{"status":"ok"}`. It runs `SELECT 1` in a read-only transaction. The result is cached for 10 s, and the endpoint is rate limited.
- The API docs UI is on local and staging only. On staging it is only on the tailnet. In production the route is disabled in the code.
- Staging is behind basic auth, with random demo passwords. See [ADR 0012](0012-ci-cd-pipeline.md).
- The server: SSH key only, only through Tailscale, non-root user, firewall, fail2ban, automatic security updates.
- The GitHub account uses passkey or hardware-key 2FA, because it controls the deploys.
- Secrets: GitHub Environment secrets, strict file permissions on the server, gitleaks in hooks and CI.
- No personal data in logs. A test checks the log processor.
- 2FA (TOTP) is in the backlog.

## Explanation

- Security headers tell the browser how to protect the site. Example: "use only HTTPS".
- A CSP tells the browser where scripts may come from. An injected script does not run.
- The ZAP baseline scan browses the site like a user and reports problems. It does not attack.
