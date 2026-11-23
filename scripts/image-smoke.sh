#!/usr/bin/env bash
# Image smoke test (ADR 0012): start the prod api and web images together, then send GET requests.
# It needs no database server: the api uses SQLite in memory for this test only.
# Usage: scripts/image-smoke.sh <api image> <web image>
set -euo pipefail

api_image=$1
web_image=$2
id=smoke-$$
net=$id-net
out=$(mktemp)

cleanup() {
  rm -f "$out"
  docker rm -f "$id-api" "$id-web" > /dev/null 2>&1 || true
  docker network rm "$net" > /dev/null 2>&1 || true
}
trap cleanup EXIT

docker network create "$net" > /dev/null
docker run -d --name "$id-api" --network "$net" --network-alias api \
  -e APP_ENV=production -e APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" \
  -e LOG_CHANNEL=stderr -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: \
  -e SESSION_DRIVER=array -e CACHE_STORE=array -e QUEUE_CONNECTION=sync \
  "$api_image" > /dev/null
docker run -d --name "$id-web" --network "$net" -p 127.0.0.1::80 "$web_image" > /dev/null
port=$(docker port "$id-web" 80/tcp | head -n 1 | sed 's/.*://')
base=http://127.0.0.1:$port

fail=0
# Arguments: path, expected HTTP status, text that the body must contain.
check() {
  local path=$1 want=$2 text=$3 body code i
  for i in $(seq 1 30); do
    code=$(curl -s -o "$out" -w '%{http_code}' "$base$path" || true)
    [ "$code" = "$want" ] && break
    sleep 1
  done
  body=$(cat "$out")
  if [ "$code" = "$want" ] && printf '%s' "$body" | grep -qF -- "$text"; then
    echo "image-smoke: GET $path -> $code"
  else
    echo "ERROR: GET $path -> $code (want $want with \"$text\")"
    fail=1
  fi
}
check / 200 '<div id="root">'
check /today 200 '<div id="root">'
check /health 200 '"status":"ok"'
check /manifest.webmanifest 200 'Pond Habits'

if [ "$fail" != 0 ]; then
  echo "--- api logs"
  docker logs "$id-api" 2>&1 | tail -n 30
  echo "--- web logs"
  docker logs "$id-web" 2>&1 | tail -n 30
fi
exit "$fail"
