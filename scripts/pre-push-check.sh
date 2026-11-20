#!/usr/bin/env bash
# Pre-push ref check (ADR 0007). Git gives one line for each ref on stdin:
#   <local ref> <local sha> <remote ref> <remote sha>
# It blocks: a push to main, a force push (not a fast-forward), and a wrong branch name.
set -euo pipefail

zero=0000000000000000000000000000000000000000
fail=0
while read -r local_ref local_sha remote_ref remote_sha; do
  [ -z "${local_ref:-}" ] && continue
  if [ "$remote_ref" = refs/heads/main ]; then
    echo "ERROR: do not push to main. Open a pull request." >&2
    fail=1
    continue
  fi
  [ "$local_sha" = "$zero" ] && continue # a branch delete
  branch=${remote_ref#refs/heads/}
  if ! printf '%s' "$branch" | grep -qE '^(feat|fix|chore|docs)/[a-z0-9._-]+$'; then
    echo "ERROR: the branch name '$branch' must start with feat/, fix/, chore/ or docs/ (ADR 0007)." >&2
    fail=1
  fi
  if [ "$remote_sha" != "$zero" ] && ! git merge-base --is-ancestor "$remote_sha" "$local_sha" 2> /dev/null; then
    echo "ERROR: this push replaces commits on '$branch' (a force push). Force push is blocked." >&2
    echo "       If the remote commit is only missing here, run 'git fetch' and try again." >&2
    fail=1
  fi
done
exit "$fail"
