# ADR 0005: Authentication and registration

Status: Accepted (2026-11-20). Changed in place on 2026-11-27 with the owner's approval: see "Change 2026-11-27".

## Decision

- Laravel Sanctum in SPA cookie mode: session cookie, CSRF protection, no tokens in browser storage.
- The frontend and the API share the parent domain `pondhabits.com`. This costs nothing extra.
- Token mode (bearer) is added only when a native app comes.
- Registration is by invite. The flow has verify email, login and password reset.
- The first admin comes from the command `make admin` (`php artisan app:create-admin`).
- Passwords: 12 characters minimum, breached password check (Have I Been Pwned, k-anonymity).
- Login is rate limited.
- Invite and password reset mails have strict rate limits for each email address and each IP address.
- A global daily cap on app mail stays below the provider quota. Each mail type (invite, verify, reset) has its own budget, so password resets always keep a reserve.
- When the cap is close, the app sends a Sentry warning event.
- 2FA (TOTP) is in the backlog.

## Mail

- Local and CI: Mailpit. Tests check the mails through the Mailpit API.
- Staging: a Mailpit container on the VPS catches all mail. No staging mail reaches a real inbox. The Mailpit UI is only on the tailnet.
- Production: Resend or Brevo (free tier). The choice is made before the first production deploy.
- The domain needs SPF, DKIM and DMARC records.

## Change 2026-11-27

- Production mail: **Brevo** (Free plan, a French company). SMTP relay on port 587 with an SMTP key. The key is limited to the VPS addresses ("Authorised IPs" in Brevo).
- DNS: the Brevo code and two DKIM records (CNAME, "DNS only"). DMARC passes through the DKIM signature, so SPF stays `v=spf1 -all` and DMARC stays `p=reject` ([ADR 0026](0026-dns-without-proxy.md)).
- The production secrets are `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS` in the GitHub Environment `production`.
