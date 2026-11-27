# Server setup with Ansible

Ansible sets up the VPS from the repo, so a new server is the same as the old one ([ADR 0011](../../docs/adr/0011-hosting-vps.md)).
`make provision` runs Ansible in a container. The workstation needs only Docker and an SSH agent.

## Playbooks

| Playbook | Command | Connection | What it does |
|---|---|---|---|
| `bootstrap.yml` | `make provision-bootstrap` | Public IPv4, provider's default user | Once on a new server: host name, updates, admin user, joins the tailnet |
| `site.yml` | `make provision` | Tailnet name, admin user, sudo password | Each time: all roles. It closes SSH on the public address. |

`site.yml` stops when `ansible_host` is not a tailnet address, so it cannot lock you out.

## Roles

| Role | Result |
|---|---|
| `base` | Host name, UTC, security updates each day, reboot at a quiet time when an update needs it |
| `tailscale` | Tailscale from the official repository, device tag `tag:server` |
| `users` | Admin user (sudo with a password, key only). Deploy user (no sudo, `docker` group). The default user is locked. |
| `ssh` | Key only, no root, only the admin and the deploy user. A config check runs before each restart. |
| `firewall` | ufw: 80, 443 (TCP and UDP) and Tailscale's port from the internet. SSH on the tailnet interface only. |
| `fail2ban` | SSH jail with the default values |
| `docker` | Docker Engine and Compose from Docker's repository. Log rotation, `no-new-privileges`. |
| `edge` | The edge Caddy (`/opt/pond-edge`): the only container with public ports. HTTPS, HSTS, staging basic auth and `noindex`, `www.` redirect. `caddy validate` checks each new Caddyfile. |
| `tailnet_serve` | On the tailnet only: Mailpit UI (`:8025`) and staging without basic auth (`:8081`, API docs at `/docs/api`). Plain HTTP inside WireGuard. |

Two facts to know:

- Docker writes its own firewall rules for published ports, and ufw does not see them. The Compose files on the server publish only Caddy's ports.
- The `docker` group can control Docker, and that is the same as root. Only the deploy job uses the deploy user, and only through the tailnet.

## Private inventory

`local/inventory/hosts.yml` (git ignores `local/`). The example uses placeholders:

```yaml
all:
  hosts:
    vps:
      ansible_host: 203.0.113.10        # bootstrap: public IPv4. After: <host>.<tailnet>.ts.net
      ansible_user: ubuntu              # bootstrap: provider's user. After: the admin user
      admin_user: <your login name>
      admin_ssh_public_key: "ssh-ed25519 AAAA... you@laptop"
      deploy_user: <deploy login name>
      server_hostname: <host name>
      unattended_reboot_time: "HH:MM"
      staging_basic_auth_user: <user name for staging>
      staging_basic_auth_hash: "<output of: docker run --rm -it caddy:2.11-alpine caddy hash-password>"
```

The deploy key (`deploy_ssh_public_key`) comes with the deploy workflows.

## First run on a new server

1. In the Tailscale admin console, make an auth key: one-off, pre-approved, tag `tag:server`.
2. Make the sudo password hash: `openssl passwd -6`. Keep the password in your password manager.
3. `make provision-bootstrap`. Give the hash and the auth key when it asks.
4. Test the new login over the tailnet: `ssh <admin_user>@<server_hostname>`.
5. In the inventory, set `ansible_host` to the tailnet name and `ansible_user` to the admin user.
6. `make provision`. Give the sudo password when it asks.
7. Check that SSH on the public IPv4 does not answer.

If a run locks you out, use the provider's web console (KVM) to log in and repair.

## Checks

`make lint-infra` (part of `make lint-repo`): `ansible-playbook --syntax-check` and `ansible-lint` with the `production` profile.
