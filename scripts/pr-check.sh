#!/usr/bin/env bash
# PR rules (ADR 0007, ADR 0010, ADR 0012). CI runs it on each PR with the PR data in variables:
#   PR_TITLE   the title. A squash merge uses it as the commit message, so it follows Conventional Commits.
#   PR_BRANCH  the source branch: feat/, fix/, chore/ or docs/.
#   PR_BODY    the PR text. It must keep the "Checks" boxes of the PR template.
# The two boxes must agree with the diff (compared with the base ref):
#   "changes a recorded decision"  <=>  the diff changes docs/adr/ or docs/privacy.md
#   "changes scripts/ or .claude/"  <=>  the diff changes scripts/ or .claude/
# Usage: scripts/pr-check.sh [base-ref]   (default: origin/main)
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

base=${1:-origin/main}
fail=0
err() {
  echo "ERROR: $1"
  fail=1
}

# 1. Title
tmp=$(mktemp)
trap 'rm -f "$tmp"' EXIT
printf '%s\n' "${PR_TITLE:-}" > "$tmp"
if ! bash scripts/commit-msg-check.sh "$tmp" > /dev/null 2>&1; then
  err "the PR title is not a Conventional Commit, for example \"feat(pomodoro): add the focus timer\"."
fi

# 2. Branch name (the same rule as the pre-push hook)
if ! printf '%s' "${PR_BRANCH:-}" | grep -qE '^(feat|fix|chore|docs)/[a-z0-9._-]+$'; then
  err "the branch name must start with feat/, fix/, chore/ or docs/."
fi

# 3. The boxes in the PR text agree with the diff
changed=$(git diff --name-only "$base"...HEAD)

# Arguments: box text, regex for the paths, name for the message.
box() {
  local text=$1 re=$2 name=$3 line checked=0 touched=0
  line=$(printf '%s\n' "${PR_BODY:-}" | grep -F -- "$text" | head -n 1 || true)
  if [ -z "$line" ]; then
    err "the PR text has no box \"$text\". Use the PR template."
    return
  fi
  printf '%s' "$line" | grep -qE '^[[:space:]]*[-*][[:space:]]*\[[xX]\]' && checked=1
  printf '%s\n' "$changed" | grep -qE "$re" && touched=1
  if [ "$touched" = 1 ] && [ "$checked" = 0 ]; then
    err "the diff changes $name, but the box \"$text\" is not checked."
  elif [ "$touched" = 0 ] && [ "$checked" = 1 ]; then
    err "the box \"$text\" is checked, but the diff does not change $name."
  fi
}
box "This PR changes a recorded decision or the privacy baseline" \
  '^docs/adr/[0-9]{4}-|^docs/privacy\.md$' "an ADR or docs/privacy.md"
box 'This PR changes `scripts/` or `.claude/`' '^(scripts|\.claude)/' "scripts/ or .claude/"

if [ "$fail" = 0 ]; then
  echo "pr-check: title, branch name and PR boxes are correct."
fi
exit "$fail"
