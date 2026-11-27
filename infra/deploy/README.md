# Server stacks

The Compose files that run the app on the VPS ([ADR 0012](../../docs/adr/0012-ci-cd-pipeline.md)).
Staging and production use the same `compose.yaml`, each with its own override file and its own folder on the server.

## Layout on the server

```text
~deploy/staging/      compose.yaml  compose.staging.yaml     deploy.sh  .env  app.env  db.env
~deploy/production/   compose.yaml  compose.production.yaml  deploy.sh  .env  app.env  db.env  backups/
```

- `.env`: image prefix, image tag and edge network name. No secrets.
- `app.env` and `db.env`: secrets, mode `0600`, owner: the deploy user. `ci-deploy.sh` writes them from the GitHub Environment secrets on each deploy.
- Examples with placeholders: [env.example/](env.example/).

## Isolation

| Item | Staging | Production |
|---|---|---|
| Compose project | `pond-staging` | `pond-production` |
| Networks | own `app` and `data`, edge network `edge-staging` | own `app` and `data`, edge network `edge-production` |
| Database | own PostgreSQL container, user and volume | own PostgreSQL container, user and volume |
| Secrets | own `app.env`, `db.env` | own `app.env`, `db.env` |
| Mail | Mailpit (catches all mail) | Mail provider |
| Resource limits | Small: staging cannot starve production | Large, with room for staging and the restore test |

- The `data` network is `internal`: the database has no internet access.
- No service publishes a port. Only the edge Caddy does (ports 80 and 443). Docker's port rules go around ufw.
- The app containers drop all Linux capabilities and run as `www-data`. The web container has a read-only file system.
- App logs: daily files in the `logs` volume, deleted after 14 days (privacy baseline item 8).

## Deploy flow

The workflows `deploy-staging.yml` (after "Images" on `main`) and `deploy-production.yml` (tag `v*`, after the owner's approval) do this:

1. The runner joins the tailnet as `tag:ci` with its OIDC token. It can reach only SSH on the server.
2. `ci-deploy.sh` (on the runner) writes the env files and copies them with the Compose files and `deploy.sh`.
3. `deploy.sh` (on the server): new image tag, pull, database up, **production: database dump to `backups/`** (10 newest), migrations, all services up, **staging: demo data with a new random password**, smoke test.
4. On any failure, `deploy.sh` goes back to the previous image tag. The database stays migrated.

Rule: a migration must work with the previous release too ("expand, then contract"). Example: add a column in one release, use it in the next, remove the old one in a third.

Staging demo users get a random password that nobody knows. To log in as a demo user, use "Forgot password" and read the mail in Mailpit.

## Commands (run in the stack folder)

```bash
docker compose -p pond-staging -f compose.yaml -f compose.staging.yaml up -d --wait
docker compose -p pond-staging -f compose.yaml -f compose.staging.yaml run --rm api php artisan migrate --force
docker compose -p pond-staging -f compose.yaml -f compose.staging.yaml ps
```

The deploy workflow runs these steps (Phase 4, task 8).

## Test

`scripts/stack-smoke.sh` starts a stack with local images, migrates, and checks `/`, `/health`, `/api/me`, the service states, and that the database cannot reach the internet. `make images` and the PR job `image-build` run it for staging and production.
