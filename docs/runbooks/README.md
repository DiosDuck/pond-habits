# Runbooks

Step-by-step procedures for the server. Read the whole procedure before you start.

| Runbook | Use it when |
|---|---|
| [deploy.md](deploy.md) | You release to production, a deploy fails, you roll back, or you change a secret or a server fact |
| [restore.md](restore.md) | Data is lost or wrong, a migration broke data, or the server is lost |

## What you need

- Your laptop (or another device) in the tailnet, with your SSH key.
- The password manager entry "Pond Habits – VPS": sudo password, SSH key, private inventory, restic password, B2 key.
- The live values (server addresses, user names, host names, job times) are in the private inventory, not here ([ADR 0023](../adr/0023-public-repo-content.md)). The commands use placeholders in `<angle brackets>`.

## Shortcuts used in the runbooks

Log in to the server over the tailnet:

```bash
ssh <admin user>@<server host name>
```

On the server, one command for each stack. The stack folders are in the home folder of the deploy user (`$DEPLOY_HOME`):

```bash
DEPLOY_HOME=$(getent passwd <deploy user> | cut -d: -f6)
PROD="sudo docker compose -p pond-production --project-directory $DEPLOY_HOME/production -f $DEPLOY_HOME/production/compose.yaml -f $DEPLOY_HOME/production/compose.production.yaml"
STAGING="sudo docker compose -p pond-staging --project-directory $DEPLOY_HOME/staging -f $DEPLOY_HOME/staging/compose.yaml -f $DEPLOY_HOME/staging/compose.staging.yaml"
```

Examples: `$PROD ps`, `$PROD logs --tail 50 api`, `$PROD exec api php artisan about`.

App logs (daily files, 14 days): `$PROD exec api sh -c 'tail -n 50 storage/logs/laravel-$(date -u +%F).log'`.
