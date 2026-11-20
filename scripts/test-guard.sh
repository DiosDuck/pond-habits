#!/usr/bin/env bash
# Test guard (ADR 0008): fail when the branch changes or deletes a test file that exists on the base.
# New test files are allowed. The owner allows a change with the PR label "tests-change-approved".
# CI sets TESTS_CHANGE_APPROVED=1 when the PR has that label.
# Usage: scripts/test-guard.sh [base-ref]   (default: origin/main)
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"
# TEST_RE: the same test paths that the Claude hooks protect.
. .claude/hooks/lib.sh

base=${1:-origin/main}
changed=$(git diff --name-status --diff-filter=MDR "$base"...HEAD |
  awk '{ print $2 }' | grep -E "$TEST_RE" || true)

if [ -z "$changed" ]; then
  echo "test-guard: no existing test was changed or deleted."
  exit 0
fi

echo "test-guard: these existing tests were changed, renamed or deleted:"
printf '  %s\n' $changed
if [ "${TESTS_CHANGE_APPROVED:-}" = 1 ]; then
  echo "test-guard: the owner approved the change (label tests-change-approved)."
  exit 0
fi
echo "ERROR: a change to an existing test needs the label tests-change-approved. Only the owner adds it."
exit 1
