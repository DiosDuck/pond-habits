#!/usr/bin/env bash
# The deploy job's part (ADR 0012). It runs on the GitHub runner, after the runner joined the tailnet.
# It writes the env files from the Environment secrets, copies the stack files to the server
# and runs deploy.sh there. The secret values never go to the log.
# Usage: ci-deploy.sh <staging|production> <image tag>
# Environment: DEPLOY_HOST, DEPLOY_USER, DEPLOY_SSH_KEY, DEPLOY_KNOWN_HOSTS, APP_KEY, DB_PASSWORD;
# production also MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS.
# Optional: SENTRY_LARAVEL_DSN (empty: no error reports from this stack).
# shellcheck disable=SC2029 # $stack and $tag expand on the runner on purpose (checked values)
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

stack=$1
tag=$2
case "$stack" in
  staging) url=https://staging.pondhabits.com ;;
  production) url=https://pondhabits.com ;;
  *) echo "ci-deploy: unknown stack '$stack'" >&2; exit 2 ;;
esac

required=(DEPLOY_HOST DEPLOY_USER DEPLOY_SSH_KEY DEPLOY_KNOWN_HOSTS APP_KEY DB_PASSWORD)
[ "$stack" = production ] && required+=(MAIL_HOST MAIL_USERNAME MAIL_PASSWORD MAIL_FROM_ADDRESS)
missing=()
for name in "${required[@]}"; do
  [ -n "${!name:-}" ] || missing+=("$name")
done
if [ ${#missing[@]} -gt 0 ]; then
  echo "ci-deploy: the '$stack' environment has no value for: ${missing[*]}" >&2
  exit 1
fi

# Laravel needs "base64:" and a key of 32 bytes (AES-256). A wrong key breaks each request with a
# session, so stop here, before the server changes.
if [[ ! "$APP_KEY" =~ ^base64:[A-Za-z0-9+/]{43}=$ ]] || [ "$(printf '%s' "${APP_KEY#base64:}" | base64 -d 2> /dev/null | wc -c)" != 32 ]; then
  echo "ci-deploy: APP_KEY in the '$stack' environment must be 'base64:' and 32 random bytes," >&2
  echo "ci-deploy: no quotes, no spaces. Make one with: echo \"base64:\$(openssl rand -base64 32)\"" >&2
  exit 1
fi

umask 077
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT

# SSH: the deploy key and the server host key (no "trust on first use" in CI).
printf '%s\n' "$DEPLOY_SSH_KEY" > "$work/key"
printf '%s\n' "$DEPLOY_KNOWN_HOSTS" > "$work/known_hosts"
ssh_opts=(-i "$work/key" -o IdentitiesOnly=yes -o UserKnownHostsFile="$work/known_hosts"
  -o StrictHostKeyChecking=yes -o BatchMode=yes -o ConnectTimeout=20)
target="$DEPLOY_USER@$DEPLOY_HOST"

# The env files. Values in double quotes: Compose reads them literally.
{
  echo "APP_NAME=\"Pond Habits\""
  echo "APP_ENV=$stack"
  echo "APP_KEY=$APP_KEY"
  echo "APP_DEBUG=false"
  echo "APP_URL=$url"
  echo "DB_DATABASE=pond"
  echo "DB_USERNAME=pond"
  echo "DB_PASSWORD=$DB_PASSWORD"
  echo "SESSION_DRIVER=database"
  echo "SESSION_SECURE_COOKIE=true"
  echo "SANCTUM_STATEFUL_DOMAINS=${url#https://}"
  echo "CACHE_STORE=database"
  echo "QUEUE_CONNECTION=database"
  if [ -n "${SENTRY_LARAVEL_DSN:-}" ]; then
    echo "SENTRY_LARAVEL_DSN=$SENTRY_LARAVEL_DSN"
  fi
  if [ "$stack" = production ]; then
    echo "MAIL_MAILER=smtp"
    echo "MAIL_HOST=$MAIL_HOST"
    echo "MAIL_PORT=587"
    echo "MAIL_USERNAME=$MAIL_USERNAME"
    echo "MAIL_PASSWORD=$MAIL_PASSWORD"
    echo "MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS"
    echo "MAIL_FROM_NAME=\"Pond Habits\""
  fi
} > "$work/app.env"
printf 'POSTGRES_DB=pond\nPOSTGRES_USER=pond\nPOSTGRES_PASSWORD=%s\n' "$DB_PASSWORD" > "$work/db.env"

echo "ci-deploy: copy the files for $stack"
ssh "${ssh_opts[@]}" "$target" "mkdir -p ~/$stack && chmod 700 ~/$stack"
scp -q "${ssh_opts[@]}" infra/deploy/compose.yaml "infra/deploy/compose.$stack.yaml" infra/deploy/deploy.sh \
  "$work/app.env" "$work/db.env" "$target:$stack/"

echo "ci-deploy: run deploy.sh on the server"
ssh "${ssh_opts[@]}" "$target" "chmod 600 ~/$stack/app.env ~/$stack/db.env && bash ~/$stack/deploy.sh $stack $tag"
