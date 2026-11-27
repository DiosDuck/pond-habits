# Server stacks

The Compose files that run the app on the VPS ([ADR 0012](../../docs/adr/0012-ci-cd-pipeline.md)).
Staging and production use the same `compose.yaml`, each with its own override file and its own folder on the server.

## Layout on the server

```text
<base folder>/staging/      compose.yaml  compose.staging.yaml     .env  app.env  db.env
<base folder>/production/   compose.yaml  compose.production.yaml  .env  app.env  db.env
```

- `.env`: image prefix, image tag and edge network name. No secrets.
- `app.env` and `db.env`: secrets, mode `0600`, owner: the deploy user. The deploy job writes them from the GitHub Environment secrets.
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

## Commands (run in the stack folder)

```bash
docker compose -p pond-staging -f compose.yaml -f compose.staging.yaml up -d --wait
docker compose -p pond-staging -f compose.yaml -f compose.staging.yaml run --rm api php artisan migrate --force
docker compose -p pond-staging -f compose.yaml -f compose.staging.yaml ps
```

The deploy workflow runs these steps (Phase 4, task 8).

## Test

`scripts/stack-smoke.sh` starts a stack with local images, migrates, and checks `/`, `/health`, `/api/me`, the service states, and that the database cannot reach the internet. `make images` and the PR job `image-build` run it for staging and production.
