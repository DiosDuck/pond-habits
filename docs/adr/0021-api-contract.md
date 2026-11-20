# ADR 0021: API contract

Status: Accepted (2026-11-20)

## Decision

- Scramble generates the OpenAPI spec from the Laravel code.
- The spec file is in the repo.
- openapi-typescript generates the TypeScript API types for the frontend.
- MSW mocks in component tests use the same spec.
- CI fails if the spec or the types differ from the generated output.
- The Scramble docs UI is at `/docs/api` on local. On staging it is only on the tailnet.
- In production the route is disabled in the code, not only in Caddy.

## Consequences

- An API change gives compile errors in the frontend at once.
- The API documentation is always the same as the code.
