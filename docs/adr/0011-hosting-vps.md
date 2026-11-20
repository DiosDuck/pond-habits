# ADR 0011: Hosting on a VPS

Status: Accepted (2026-11-20)

## Context

The owner wants to learn deploy pipelines. The budget is low. Staging and production run on one machine, so the machine needs about 4 GB RAM.

## Decision

- One EU VPS with Docker Compose. Staging and production are separate Compose projects.
- First choice: OVHcloud VPS-1. Check Hetzner stock again before the VPS is ordered.
- Not for production: Contabo (CPU reports), Oracle Always Free (account risk).
- Images are multi-arch (`linux/amd64` and `linux/arm64`). The provider can change at low cost.
- Ansible in `infra/` sets up the server. It runs from a container (`make provision`).
- The public Ansible roles read the live values (server address, SSH user, host names) as variables. The values are in a private inventory in `local/` and in GitHub secrets.
- Server setup: non-root user, SSH key only, firewall, fail2ban, automatic security updates, Docker, backup jobs.
- SSH is not open to the internet. SSH works only through Tailscale. The internet sees only HTTP and HTTPS (Caddy).
- Staging and production are always isolated. See [ADR 0012](0012-ci-cd-pipeline.md).

## Consequences

- The server is our job: updates, firewall, backups. Ansible makes this repeatable.
- Tailscale is one more tool to learn. It removes the public SSH attack surface.
