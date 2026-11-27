# ADR 0013: Backups and restore tests

Status: Accepted (2026-11-20). Changed in place on 2026-11-27 with the owner's approval: see "Change 2026-11-27".

## Decision

- A `backup` container on the VPS runs each night: `pg_dump`, then restic, then Backblaze B2.
- restic encrypts the data on the server before upload. B2 never sees plain data.
- restic de-duplicates, so only changed blocks are uploaded.
- Retention: 7 daily, 4 weekly, 6 monthly snapshots (about 6 months).
- After each backup, the job pings a Better Stack heartbeat. A missing ping or a "fail" ping sends an alert email.
- A backup also runs before each production migration.

## Restore test

- Monthly, at night, on the VPS (not on GitHub, because the repo and its logs are public).
- Limits: `cpus: 0.5`, `memory: 512m`. Production always keeps the rest of the machine.
- Steps: restore the newest snapshot into a temporary PostgreSQL, check that the tables exist, the row counts are more than 0 and the newest record is less than 26 hours old, send the result, remove the temporary database.
- The test pings a Better Stack heartbeat. A failure or a missing ping sends an alert email.

## Key safety

The restic password is in GitHub Secrets and in the owner's password manager. Without it, no backup can be read.

## Change 2026-11-27

- Systemd timers on the VPS start the backup and the restore test (Ansible role `backup`). Each job runs official containers only: `pg_dump` in the production database container, restic in the `restic/restic` image. There is no long-running backup container.
- The restic password, the B2 key and the heartbeat URLs are in the private Ansible inventory and in a root-only file on the server (`/etc/pond-backup/env`, mode 0600), not in GitHub Secrets: the backup is server setup (`make provision`), not part of a deploy. The owner keeps a copy in the password manager.
- B2 bucket in the EU (eu-central). The B2 key works only for this bucket.
- The restore test checks the snapshot age (less than 26 hours) and that `migrations` and `users` have rows. It runs in a container with no network.
- Each job reports its result once, from its exit hook: a success ping, or a `/fail` ping.

## Consequences

- Cost: 0 (B2 free tier).
- The manual steps are in `docs/runbooks/restore.md`. It is written with the first deploy.
