# ADR 0023: Public repository content

Status: Accepted (2026-11-20)

## Context

The repo is public. An audit of the first docs found workstation details, a known demo password on staging, and details that help an attacker. Nothing was committed, so nothing leaked.

## Decision

Do not put these in a tracked file:

- Personal data: names, emails, user names, home paths, locations.
- Workstation details: OS and version, CPU arch, installed tools, other projects, disk sizes.
- Credentials: real passwords, keys, tokens. Only the place where a secret is kept may be named.
- Costs and prices: monthly costs, provider prices, the prices of the options that were not chosen. They are in `local/costs.md`.
- Live infrastructure values: server address, SSH user, Tailscale host names, exact job times, rate-limit values, free tier limits.

These are allowed: the owner's GitHub username (in `LICENSE`, in the repo URL and as the commit author), the stack, the provider type, the domain, the names of the external services, the security baseline, and dev-setup policy (for example "the dev setup needs only docker, git and make").

The commit email is the owner's choice. It is in the public git history, not in a tracked file.

## Private notes

- Private notes are in `local/`. `.gitignore` excludes it. It has no backup. The owner accepts this.

## Enforcement

- A sensitive-pattern check runs in pre-commit and in CI. It fails on home paths, private IP addresses, email addresses (except `@demo`, `@example.com`, `noreply@`), and words from the private deny-list `local/deny-list.txt`.
- gitleaks runs in pre-commit and in CI.
- A Claude rule: "Do not write workstation or personal details into tracked files."
- Before the first commit, the owner reviews the full diff.

## If something leaks

- Treat the secret as leaked. Rotate it at once.
- Do not rewrite git history. Force push stays blocked.
