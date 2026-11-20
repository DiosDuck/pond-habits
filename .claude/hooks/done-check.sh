#!/usr/bin/env bash
# Claude Code Stop hook: run "make check" before Claude reports "done" (ADR 0019).
# - No "check" target in the Makefile yet: do nothing.
# - No change since the last green run: do nothing.
# - "make check" fails: block the stop once, so that Claude fixes it or reports it.
#   On the second stop, let Claude stop, to prevent a loop.
set -u
. "$(dirname "$0")/lib.sh"

input=$(read_input)
cd "${CLAUDE_PROJECT_DIR:-$PWD}" || exit 0

[ -f Makefile ] || exit 0
make -n check >/dev/null 2>&1 || exit 0

# A fingerprint of the working tree: HEAD, status, and the content of changed files.
state=$(
  {
    git rev-parse HEAD 2>/dev/null
    git status --porcelain
    git ls-files -mo --exclude-standard -z | xargs -0 -r git hash-object -- 2>/dev/null
  } | git hash-object --stdin
)
stamp="$(git rev-parse --git-dir)/claude-check-ok"
[ -f "$stamp" ] && [ "$(cat "$stamp")" = "$state" ] && exit 0

if out=$(make check 2>&1); then
  printf '%s' "$state" > "$stamp"
  exit 0
fi

if printf '%s' "$input" | grep -qE '"stop_hook_active"[[:space:]]*:[[:space:]]*true'; then
  exit 0
fi

{
  echo "make check FAILED. Fix it, or tell the owner that it fails and why. Last lines:"
  printf '%s\n' "$out" | tail -n 40
} >&2
exit 2
