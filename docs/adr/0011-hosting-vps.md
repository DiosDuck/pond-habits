# ADR 0011: Hosting on a VPS

Status: Accepted (2026-11-20). Changed in place on 2026-11-27 with the owner's approval: see "Change 2026-11-27".

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

## Change 2026-11-27

At the order, OVHcloud VPS-1 was not available, and the only location with stock in the EU area was the United Kingdom. Hetzner was still not available.

- Plan: OVHcloud VPS-2 (4 vCores, 8 GB RAM, 75 GB NVMe). It replaces VPS-1. The contract does not allow a downgrade later.
- Location: a data centre in the United Kingdom. The UK has an EU adequacy decision (GDPR Art. 45), so no extra transfer contract is necessary. The location cannot change later; a move needs a new VPS.
- OS: Ubuntu 26.04 LTS (standard support until 2031). Docker supports it officially.
- Consequence: the OVHcloud data processing agreement does not protect data in a non-EU data centre against requests from non-EU authorities. The owner accepts this risk for about 10 users.
- Consequence: the privacy notice must name the United Kingdom as the hosting country.
- The OVHcloud daily backup option stays on. It does not replace the off-site backup in [ADR 0013](0013-backups.md).

## Consequences

- The server is our job: updates, firewall, backups. Ansible makes this repeatable.
- Tailscale is one more tool to learn. It removes the public SSH attack surface.
