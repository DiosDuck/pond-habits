# Runbook: restore

Backups: [ADR 0013](../adr/0013-backups.md), Ansible role `backup`. Shortcuts (`$PROD`, `$DEPLOY_HOME`): [README.md](README.md).

| Source | Where | Age | Format |
|---|---|---|---|
| Dump before each production migration | `$DEPLOY_HOME/production/backups/pre-*.sql.gz` (10 newest) | The last deploys | SQL text, gzip |
| Nightly restic snapshot | B2 bucket, encrypted with the restic password | 7 daily, 4 weekly, 6 monthly | `pg_dump` custom format |

Without the restic password, nobody can read a snapshot. It is in the password manager and in `/etc/pond-backup/env` (root only).

## Before each restore

1. Write down the time and the reason.
2. Stop the app, but not the database: `$PROD stop web api worker scheduler`. Visitors get an error page (502) until the end.
3. Make a dump of the current state, even when it is wrong: `$PROD exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB"' | gzip | sudo tee /root/before-restore-$(date -u +%Y%m%dT%H%MZ).sql.gz > /dev/null`
4. Make the database empty: `$PROD exec -T db sh -c 'dropdb -U "$POSTGRES_USER" "$POSTGRES_DB" && createdb -U "$POSTGRES_USER" "$POSTGRES_DB"'`

## Part A: restore a dump from before a migration

1. List the dumps: `sudo ls -lt $DEPLOY_HOME/production/backups/`
2. Restore one: `sudo zcat $DEPLOY_HOME/production/backups/<file> | $PROD exec -T db sh -c 'psql -q -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB"'`
3. Deploy the release that matches the dump ([deploy.md](deploy.md), "Roll back by hand"), then continue with "After each restore".

## Part B: restore a nightly snapshot from B2

1. Open a root shell with the backup settings: `sudo -i`, then `set -a; . /etc/pond-backup/env; set +a`
2. Define restic: `restic() { docker run --rm -i -e RESTIC_REPOSITORY -e RESTIC_PASSWORD -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY "$RESTIC_IMAGE" "$@"; }`
3. List the snapshots: `restic snapshots --host pond --tag production`
4. Restore one (an ID from the list, or `latest`), in the same root shell:
   `restic dump --host pond --tag production <ID> production.dump | docker exec -i $(docker ps -qf label=com.docker.compose.project=pond-production -f label=com.docker.compose.service=db) sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --no-owner --no-privileges'`
5. `exit` the root shell. Continue with "After each restore".

## After each restore

1. Start the app: `$PROD up -d --wait`
2. Check: `https://pondhabits.com/health` and a login on a phone.
3. Privacy: a restore brings back accounts that users deleted after the backup time. Search the domain log for `user.deleted` events after that time, and delete those accounts again with `make delete-user` (privacy baseline item 2).
4. Write down what you did and the result.

## Part C: the server is lost

1. Order a new VPS ([ADR 0011](../adr/0011-hosting-vps.md)). Put the new addresses in the private inventory.
2. `make provision-bootstrap`, then `make provision` ([infra/ansible/README.md](../../infra/ansible/README.md)). Keep `backup_timers_enabled: false` for now.
3. Change the server facts: DNS, Brevo authorised IPs, `DEPLOY_KNOWN_HOSTS` ([deploy.md](deploy.md)).
4. Deploy production: Actions → "Deploy production" → the newest release run → "Re-run jobs".
5. Restore the newest snapshot (part B, with "Before each restore" steps 2 and 4).
6. Set `backup_timers_enabled: true`, run `make provision`, and run one backup: `sudo systemctl start pond-backup`.

## Test by hand

The monthly test runs by itself. To run it now: `sudo systemctl start pond-restore-test`, then read the result: `sudo journalctl -u pond-restore-test -n 30`.
