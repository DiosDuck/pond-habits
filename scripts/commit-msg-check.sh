#!/usr/bin/env bash
# Conventional Commits check (ADR 0007) for the first line of a commit message.
# Usage: scripts/commit-msg-check.sh <file with the message>   (the commit-msg hook gives the file)
set -euo pipefail

first=$(grep -v '^#' "$1" | head -n 1)
re='^(feat|fix|chore|docs|test|refactor|perf|build|ci|style|revert)(\([a-z0-9._-]+\))?!?: [^ ].{0,99}$'

if printf '%s' "$first" | grep -qE "$re"; then
  exit 0
fi
cat >&2 << EOF
ERROR: the commit message does not follow Conventional Commits.
  Found:    $first
  Expected: <type>(<optional scope>): <summary of 100 characters or less>
  Types:    feat, fix, chore, docs, test, refactor, perf, build, ci, style, revert
  Example:  feat(pomodoro): add the focus timer
EOF
exit 1
