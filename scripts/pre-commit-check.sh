#!/usr/bin/env bash
# Pre-commit checks on the staged files only (ADR 0007). Target: less than 15 seconds.
#   Backend:  Pint (format), PHPStan, Rector (check mode)
#   Frontend: Prettier (format), ESLint
#   All:      Markdown line limit, sensitive patterns, gitleaks
# The full lint of all files runs in the pre-push hook ("make check-fast") and in CI.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

mapfile -t staged < <(git diff --cached --name-only --diff-filter=ACMR)
[ ${#staged[@]} -eq 0 ] && exit 0

export HOST_UID HOST_GID
HOST_UID=$(id -u)
HOST_GID=$(id -g)
run() { docker compose run --rm --no-deps -T "$@"; }

# Paths relative to backend/. PHPStan does not analyse tests/Arch (see phpstan.neon).
mapfile -t php < <(printf '%s\n' "${staged[@]}" | grep -E '^backend/.*\.php$' | sed 's#^backend/##' || true)
if [ ${#php[@]} -gt 0 ]; then
  echo "pre-commit: backend lint (${#php[@]} files): Pint, PHPStan, Rector"
  run api sh -c '
    ./vendor/bin/pint --test "$@" &&
    analyse=$(printf "%s\n" "$@" | grep -v "^tests/Arch/" || true) &&
    { [ -z "$analyse" ] || ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G $analyse; } &&
    ./vendor/bin/rector process --dry-run --no-progress-bar "$@"
  ' sh "${php[@]}"
fi

# Paths relative to frontend/.
mapfile -t fe < <(printf '%s\n' "${staged[@]}" | grep -E '^frontend/.*\.(ts|tsx|js|cjs|mjs|json|css|html|md)$' | sed 's#^frontend/##' || true)
if [ ${#fe[@]} -gt 0 ]; then
  echo "pre-commit: frontend lint (${#fe[@]} files): Prettier, ESLint"
  run -e npm_config_update_notifier=false web sh -c '
    npx prettier --check --ignore-unknown "$@" &&
    code=$(printf "%s\n" "$@" | grep -E "\.(ts|tsx|js|cjs|mjs)$" || true) &&
    { [ -z "$code" ] || npx eslint --max-warnings=0 $code; }
  ' sh "${fe[@]}"
fi

echo "pre-commit: Markdown line limit, sensitive patterns"
bash scripts/md-line-limit.sh "${staged[@]}"
bash scripts/sensitive-check.sh "${staged[@]}"

echo "pre-commit: gitleaks"
docker run --rm --user "$HOST_UID:$HOST_GID" -e HOME=/tmp -v "$PWD":/repo:ro \
  ghcr.io/gitleaks/gitleaks:v8.24.0 git /repo --pre-commit --staged --no-banner --log-level warn
