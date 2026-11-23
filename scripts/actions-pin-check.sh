#!/usr/bin/env bash
# Action pin check (ADR 0010): each "uses:" in a workflow or a local action must use a full commit SHA.
# A tag (v4) or a branch (main) can move to other code. A commit SHA cannot.
# Allowed:  owner/repo@<40 hex characters>   ./local/action   docker://image@sha256:<digest>
# Usage: scripts/actions-pin-check.sh [file ...]   (no files: all YAML files in .github/)
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

if [ $# -gt 0 ]; then
  files=("$@")
else
  mapfile -t files < <(find .github -type f \( -name '*.yml' -o -name '*.yaml' \) 2> /dev/null | sort)
fi

fail=0
for f in "${files[@]}"; do
  [ -f "$f" ] || continue
  while IFS=: read -r line text; do
    ref=$(printf '%s' "$text" | sed -E 's/^[[:space:]-]*uses:[[:space:]]*//; s/[[:space:]]+#.*$//; s/^["'\'']//; s/["'\'']$//')
    case "$ref" in
      ./*) continue ;;
      docker://*@sha256:*) continue ;;
    esac
    if ! printf '%s' "$ref" | grep -qE '^[A-Za-z0-9._-]+/[A-Za-z0-9._/-]+@[0-9a-f]{40}$'; then
      echo "ERROR: $f:$line: \"$ref\" is not pinned to a commit SHA."
      fail=1
    fi
  done < <(grep -nE '^[[:space:]-]*uses:' "$f" || true)
done

if [ "$fail" = 0 ]; then
  echo "actions-pin-check: all actions are pinned to a commit SHA."
else
  echo "Find the SHA of a tag: git ls-remote https://github.com/<owner>/<repo> 'refs/tags/<tag>^{}'"
fi
exit "$fail"
