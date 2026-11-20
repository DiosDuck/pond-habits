# ADR 0013: Backups and restore tests

Status: Accepted (2026-11-20)

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

## Consequences

- Cost: 0 (B2 free tier).
- The manual steps are in `docs/runbooks/restore.md`. It is written with the first deploy.
