#!/usr/bin/env bash
# New dependency check (ADR 0010): each new package needs a "Why this package" line in the PR.
# It compares backend/composer.json and frontend/package.json with the base.
# CI gives the PR text in PR_BODY. Without PR_BODY (local run), the script only lists the new packages.
# Usage: scripts/new-deps-check.sh [base-ref]   (default: origin/main)
set -euo pipefail
export LC_ALL=C # the same sort order for sort and comm
cd "$(git rev-parse --show-toplevel)"

base=${1:-origin/main}

# Print the package names in the dependency blocks of a composer.json or package.json (on stdin).
deps() {
  awk '
    /"(require|require-dev|dependencies|devDependencies)"[[:space:]]*:[[:space:]]*\{/ { block = 1; next }
    block && /^[[:space:]]*\}/ { block = 0 }
    block && match($0, /"[^"]+"[[:space:]]*:/) {
      name = substr($0, RSTART + 1, RLENGTH - 1)
      sub(/"[[:space:]]*:$/, "", name)
      print name
    }'
}

new=()
for file in backend/composer.json frontend/package.json; do
  [ -f "$file" ] || continue
  before=$(git show "$base:$file" 2> /dev/null | deps | sort -u || true)
  after=$(deps < "$file" | sort -u)
  while IFS= read -r name; do
    [ -n "$name" ] && new+=("$name")
  done < <(comm -13 <(printf '%s\n' "$before") <(printf '%s\n' "$after"))
done

if [ ${#new[@]} -eq 0 ]; then
  echo "new-deps-check: no new package."
  exit 0
fi

echo "new-deps-check: new packages:"
printf '  %s\n' "${new[@]}"

if [ -z "${PR_BODY:-}" ]; then
  echo "new-deps-check: no PR text here. CI checks that the PR explains each package."
  exit 0
fi

fail=0
if ! printf '%s' "$PR_BODY" | grep -qiE 'why (this|these) package'; then
  echo "ERROR: the PR text has no \"Why this package\" section."
  fail=1
fi
for name in "${new[@]}"; do
  if ! printf '%s' "$PR_BODY" | grep -qF -- "$name"; then
    echo "ERROR: the PR text does not explain the new package $name."
    fail=1
  fi
done
exit "$fail"
