# ADR 0002: Backend: Laravel JSON API

Status: Accepted (2026-11-20)

## Context

The owner knows PHP (Symfony, Laminas) and is learning Laravel. Claude writes most of the code. The owner reviews it. Thus the owner must be able to review the backend fast and with confidence.

## Decision

- Laravel 13 on PHP 8.5, as a JSON API only.
- Runtime: php-fpm behind Caddy. FrankenPHP and Octane are not used now.
- Queue and cache use the database driver (PostgreSQL). No Redis.
- Containers: `api`, `worker`, `scheduler`, `db`, `caddy`.
- All primary keys are UUIDv7 (`HasUuids`).

## Alternatives

- Symfony 7 with API Platform: more setup for a small app.
- Full TypeScript backend: the owner would learn and review in a less familiar language.

## Consequences

- Strong static analysis (Larastan) and Pest give good guardrails.
- A learning map `docs/learning/laravel-for-symfony-devs.md` is written with the app skeleton.
- Add Redis or Octane only with an ADR, when a metric shows the need.
