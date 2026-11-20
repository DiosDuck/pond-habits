# ADR 0004: Database: PostgreSQL

Status: Accepted (2026-11-20)

## Context

The app needs dates, intervals, recurring tasks, streaks and UUIDs. It runs on one small VPS.

## Decision

- PostgreSQL 18 in a container on the same VPS. Data is on a Docker volume.
- Staging and production use separate databases.
- Tests run on the same engine (a PostgreSQL service container). Do not use SQLite in tests.
- Primary keys are UUIDv7. The first 48 bits are the creation time, so inserts stay in order and IDs sort by time.

## Alternatives

- MySQL or MariaDB: possible, but weaker for intervals and JSON.
- A managed database: costs more than the app.
- SQLite: weak for concurrent writes and queues.

## Consequences

- Backups are our job. See [ADR 0013](0013-backups.md).
