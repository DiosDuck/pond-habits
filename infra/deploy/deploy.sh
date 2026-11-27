#!/usr/bin/env bash
# Deploy one stack on the server (ADR 0012). It runs as the deploy user, in ~/<stack>.
# The deploy workflow copies it there with the Compose files and the env files.
# Usage: deploy.sh <staging|production> <image tag, for example sha-1a2b3c4>
set -Eeuo pipefail

stack=${1:-}
tag=${2:-}
case "$stack" in
  staging | production) ;;
  *) echo "deploy: unknown stack '$stack'" >&2; exit 2 ;;
esac
if [[ ! "$tag" =~ ^sha-[0-9a-f]{7,40}$ ]]; then
  echo "deploy: the tag must look like sha-1a2b3c4, not '$tag'" >&2
  exit 2
fi

cd "$HOME/$stack"
project=pond-$stack
compose() { docker compose -p "$project" -f compose.yaml -f "compose.$stack.yaml" "$@"; }
step() { echo "deploy ($stack): $*"; }

# The tag that runs now. A failed production deploy goes back to it.
previous=$(sed -n 's/^IMAGE_TAG=//p' .env 2> /dev/null || true)

write_compose_env() {
  (
    umask 077
    printf 'IMAGE_PREFIX=%s\nIMAGE_TAG=%s\nEDGE_NETWORK=%s\n' \
      ghcr.io/diosduck/pond-habits "$1" "edge-$stack" > .env
    # Staging: the local ports for "tailscale serve" (infra/ansible/roles/tailnet_serve).
    if [ "$stack" = staging ]; then
      printf 'STAGING_WEB_PORT=8081\nMAILPIT_UI_PORT=8025\n' >> .env
    fi
  )
}

# On any failure: go back to the tag that ran before. The database stays migrated
# (production has a dump in backups/). Migrations must work with the previous release.
roll_back() {
  trap - ERR
  echo "deploy ($stack): FAILED" >&2
  compose ps >&2 || true
  compose logs --tail 30 api web >&2 || true
  if [ -n "$previous" ]; then
    step "roll back to $previous"
    write_compose_env "$previous"
    compose up -d --wait --wait-timeout 180 --remove-orphans || true
  fi
  exit 1
}
trap roll_back ERR

# /sanctum/csrf-cookie encrypts a cookie: it fails when APP_KEY is wrong.
smoke_test() {
  compose exec -T web wget -q -O - http://127.0.0.1/health | grep -q '"status":"ok"' \
    && compose exec -T web wget -q -O /dev/null http://127.0.0.1/ \
    && compose exec -T web wget -q -O /dev/null http://127.0.0.1/sanctum/csrf-cookie
}

step "image tag $tag (now: ${previous:-none})"
write_compose_env "$tag"
docker network inspect "edge-$stack" > /dev/null 2>&1 || docker network create "edge-$stack" > /dev/null
compose pull --quiet
compose up -d --wait --wait-timeout 120 db

# Production: a database dump before each migration (ADR 0013). Restic copies it off the server later.
if [ "$stack" = production ] && [ -n "$previous" ]; then
  mkdir -p backups
  dump="backups/pre-$tag-$(date -u +%Y%m%dT%H%M%SZ).sql.gz"
  step "database dump to $dump"
  (
    umask 077
    # shellcheck disable=SC2016 # the variables expand inside the db container
    compose exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB"' | gzip > "$dump"
  )
  # Keep the 10 newest dumps.
  # shellcheck disable=SC2012 # the script makes these file names: no spaces
  ls -1t backups/pre-*.sql.gz | tail -n +11 | xargs -r rm --
fi

step "migrations"
compose run --rm --no-deps api php artisan migrate --force --no-interaction

step "start all services"
compose up -d --wait --wait-timeout 180 --remove-orphans

# Staging: demo users with a new random password on each deploy. Nobody knows it; use
# "Forgot password" and Mailpit to log in as a demo user. Production never seeds.
if [ "$stack" = staging ]; then
  step "demo data"
  demo_password=$(head -c 24 /dev/urandom | base64 | tr -d '/+=')
  compose run --rm --no-deps -e DEMO_PASSWORD="$demo_password" api php artisan db:seed --force --no-interaction
  unset demo_password
fi

step "smoke test"
smoke_test || roll_back
step "OK"

# Remove images that no container uses and that are older than 7 days.
docker image prune -af --filter "until=168h" > /dev/null
