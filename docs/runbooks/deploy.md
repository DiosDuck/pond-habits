# Runbook: deploy

How the deploys work: [infra/deploy/README.md](../../infra/deploy/README.md). Shortcuts (`$PROD`): [README.md](README.md).

## Release to production

1. Test the change on staging, also on a phone.
2. Merge the release PR of release-please. The merge makes the tag `v*`.
3. GitHub → Actions → "Deploy production" → the run waits for you. Click "Review deployments" → "Approve".
4. The job dumps the database, migrates, starts the new images and runs the smoke test.
5. Open `https://pondhabits.com` on a phone. Log in.

## Rule for migrations: expand, then contract

A failed deploy goes back to the old images, but the database stays migrated. So each migration must also work with the previous release:

- Release 1: add the new column or table (nullable, or with a default). The old code ignores it.
- Release 2: the code uses the new column.
- Release 3: remove the old column, when no code uses it.

Never rename or drop a column in the same release as the code change.

## A deploy failed

1. Read the failed step in the Actions log. `ci-deploy:` messages come from the runner, `deploy (production):` messages from the server.
2. The server went back to the previous image tag by itself. Check: `$PROD ps` (all services "running") and `https://pondhabits.com/health`.
3. A wrong secret (for example `APP_KEY`) stops the job before the server changes. Fix the secret in Settings → Environments, then "Re-run jobs".
4. Data is wrong after a migration: use [restore.md](restore.md), part A (the dump from before the migration).

## Roll back by hand to an older release

1. Actions → "Deploy production" → the run of the older tag → "Re-run jobs". It deploys the images of that tag.
2. The database keeps the newer migrations. If the older code cannot work with them, restore the dump from before the newer release ([restore.md](restore.md), part A).

## Change a secret

| Secret | Steps | Effect |
|---|---|---|
| `APP_KEY` | Make a key: `echo "base64:$(openssl rand -base64 32)"`. Update the secret, then deploy. | All users must log in again |
| `DB_PASSWORD` | First on the server: `$PROD exec db psql -U pond -d pond -c "ALTER USER pond PASSWORD '<new>'"`. Then update the secret and deploy. | None. The order matters: the database keeps its old password until you change it there. |
| Mail (`MAIL_*`) | Make a new SMTP key in Brevo. Update `MAIL_PASSWORD`, deploy, then delete the old key. | None |
| `DEPLOY_SSH_KEY` | New key pair; public key in the inventory (`deploy_ssh_public_key`), `make provision`; private key in both environments | None |

Keep a copy of each new value in the password manager.

## A server fact changes (new IP address, new server)

1. Cloudflare DNS: the A and AAAA records of `@`, `www` and `staging` ([ADR 0026](../adr/0026-dns-without-proxy.md)).
2. Brevo → Security → Authorised IPs: the new addresses, or production mail stops.
3. Private inventory: the notes. A new server: see [restore.md](restore.md), part C.
4. A new host key: update `DEPLOY_KNOWN_HOSTS` in both environments (`ssh-keyscan -t ed25519 <server host name>`).

## Server setup changes

The edge Caddy, the firewall, the tailnet tools and the backups come from Ansible, not from a deploy. Change them in `infra/ansible/`, then run `make provision` ([infra/ansible/README.md](../../infra/ansible/README.md)).
