# ADR 0022: Offline scope

Status: Accepted (2026-11-20)

## Decision

- v1: the app shell is cached. The pomodoro timer works offline.
- All data entry needs the network.
- UUIDv7 keys from day 1 keep a later offline outbox cheap.

## Why full offline is not in v1

Full offline needs a local database, a sync engine, conflict rules, client-side IDs, credits checks on sync, and offline test cases for each feature. That is about 30 to 50% more work for each feature.

## Backlog

An offline outbox for "add" actions only (log a meal, tick a habit, finish a pomodoro). No offline edits or deletes, so no conflicts. The server calculates the credits.
