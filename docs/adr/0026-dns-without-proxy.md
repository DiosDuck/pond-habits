# ADR 0026: DNS at Cloudflare without the proxy

Status: Accepted (2026-11-27)

## Context

The domain `pondhabits.com` is registered at Cloudflare, so Cloudflare also hosts the DNS. For each address record, Cloudflare offers two modes:

- "DNS only": Cloudflare gives the server address. The browser connects to our server directly.
- "Proxied": the browser connects to Cloudflare. Cloudflare decrypts each request, checks it, and sends it to our server.

Cloudflare recommends "Proxied".

## Decision

- All address records are "DNS only". Caddy on the VPS ends TLS and gets its certificates from Let's Encrypt (ZeroSSL as fallback). See [ADR 0015](0015-security-baseline.md).
- Address records (A and AAAA) for `@`, `www`, `api`, `staging` and `api.staging`. Caddy redirects `www` to the apex domain. See [ADR 0020](0020-name-and-domain.md).
- CAA records allow `letsencrypt.org` and `sectigo.com` (ZeroSSL). Cloudflare adds CAA records for its own certificate authorities while Universal SSL is on.
- Until the mail provider is set up, the domain says that it sends no mail: SPF `v=spf1 -all` and DMARC `p=reject`. The mail setup changes these records.
- TTL: "Auto" (5 minutes for "DNS only" records).

## Alternatives

- "Proxied": it hides the server address, absorbs large DDoS attacks and caches static files. Not chosen:
  - Cloudflare would decrypt all traffic, also passwords and health-related data. It would be one more processor (a US company) in the privacy baseline.
  - The app limits logins and reset mails for each IP address. Behind the proxy, each request comes from a Cloudflare address. Without a trusted-proxy setup, all users would share one limit.
  - It adds a second certificate and an SSL mode that must be "Full (strict)". The default mode "Flexible" sends the traffic from Cloudflare to the server without encryption.
  - At about 10 users, the cache and the large-attack protection give little. OVHcloud filters basic attacks.

## Consequences

- The server address is public. Only HTTP and HTTPS are open to the internet. See [ADR 0011](0011-hosting-vps.md).
- Cloudflare sees only DNS queries, no user data.
- A change to "Proxied" later needs a new ADR, a trusted-proxy setup in Laravel and Caddy, the SSL mode "Full (strict)" and a privacy baseline update.
