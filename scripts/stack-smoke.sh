#!/usr/bin/env bash
# Stack smoke test (ADR 0012): start one server stack (infra/deploy) with local images, run the
# migrations, then send GET requests. It uses the same Compose files as the server, plus a
# small override that publishes the web port on 127.0.0.1 for the test.
# Usage: scripts/stack-smoke.sh <staging|production> <api image> <web image>
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

stack=$1
api_image=$2
web_image=$3
project=pond-smoke-$stack-$$
edge=$project-edge
dir=$(mktemp -d)
out=$(mktemp)

compose() {
  docker compose -p "$project" --project-directory "$dir" \
    -f infra/deploy/compose.yaml -f "infra/deploy/compose.$stack.yaml" -f "$dir/compose.smoke.yaml" "$@"
}

# shellcheck disable=SC2329 # called by the EXIT trap
cleanup() {
  compose down -v --remove-orphans > /dev/null 2>&1 || true
  docker network rm "$edge" > /dev/null 2>&1 || true
  rm -rf "$dir" "$out"
}
trap cleanup EXIT

# Both images must have the same prefix and tag in the Compose file: retag them for the test.
docker tag "$api_image" "$project/api:smoke"
docker tag "$web_image" "$project/web:smoke"
password=$(head -c 24 /dev/urandom | base64 | tr -d '/+=')
cat > "$dir/.env" <<EOF
IMAGE_PREFIX=$project
IMAGE_TAG=smoke
EDGE_NETWORK=$edge
EOF
cat > "$dir/app.env" <<EOF
APP_ENV=$stack
APP_KEY=base64:$(head -c 32 /dev/urandom | base64)
APP_DEBUG=false
APP_URL=http://localhost
DB_DATABASE=pond
DB_USERNAME=pond
DB_PASSWORD=$password
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
EOF
cat > "$dir/db.env" <<EOF
POSTGRES_DB=pond
POSTGRES_USER=pond
POSTGRES_PASSWORD=$password
EOF
cat > "$dir/compose.smoke.yaml" <<EOF
services:
  web:
    ports: ["127.0.0.1::80"]
EOF
cp infra/deploy/compose.yaml "infra/deploy/compose.$stack.yaml" "$dir/"
docker network create "$edge" > /dev/null

compose up -d --wait --wait-timeout 120 db
compose run --rm --no-deps api php artisan migrate --force --no-interaction
compose up -d --wait --wait-timeout 120
port=$(compose port web 80 | sed 's/.*://')
base=http://127.0.0.1:$port

fail=0
# Arguments: path, expected HTTP status, text that the body must contain.
check() {
  local path=$1 want=$2 text=$3 code body
  code=$(curl -s -o "$out" -w '%{http_code}' "$base$path" || true)
  body=$(cat "$out")
  if [ "$code" = "$want" ] && printf '%s' "$body" | grep -qF -- "$text"; then
    echo "stack-smoke ($stack): GET $path -> $code"
  else
    echo "ERROR ($stack): GET $path -> $code (want $want with \"$text\")"
    fail=1
  fi
}
check / 200 '<div id="root">'
check /health 200 '"status":"ok"'
check /api/me 401 'Unauthenticated'

# Each long-running service must still run (no restart loop).
for service in api worker scheduler web db; do
  state=$(compose ps --format '{{.State}}' "$service")
  if [ "$state" != "running" ]; then
    echo "ERROR ($stack): service $service is $state"
    fail=1
  fi
done

# The domain event log writes one dated file for each day (privacy baseline item 8: 14 days).
compose exec -T api php artisan tinker --execute \
  "app(App\\Support\\DomainEvents\\DomainEventLog::class)->record('smoke.checked');" > /dev/null
if compose exec -T api test -s "storage/logs/domain-$(date -u +%Y-%m-%d).log"; then
  echo "stack-smoke ($stack): the domain event log uses a dated daily file"
else
  echo "ERROR ($stack): no dated domain log file (LOG_DOMAIN_DRIVER=daily missing?)"
  fail=1
fi

# The database network has no internet access.
if compose exec -T db sh -c 'wget -q -T 5 -O /dev/null http://example.com' > /dev/null 2>&1; then
  echo "ERROR ($stack): the database container reached the internet"
  fail=1
else
  echo "stack-smoke ($stack): the database has no internet access"
fi

if [ "$fail" != 0 ]; then
  compose logs --tail 30
fi
exit "$fail"
