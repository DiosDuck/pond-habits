#!/usr/bin/env bash
# Shared parts of the Claude Code guard hooks.
# Only bash, grep, sed and tr are used. The host has only docker, git and make (ADR 0006).

# Existing test files and folders. Claude must not change or delete them (ADR 0008).
TEST_RE='(^|/)(tests?|e2e|__tests__)(/|$)|\.(test|spec)\.[cm]?[jt]sx?$|Test\.php$'

# Guardrail files. Claude must not change them. The owner changes them.
GUARD_RE='(^|/)\.claude/(settings(\.local)?\.json|hooks(/|$))|(^|/)\.githooks(/|$)'

block() {
  echo "BLOCKED by a guard hook: $1" >&2
  exit 2
}

# Read the hook input (JSON on stdin) as one line.
read_input() {
  tr -d '\n'
}

# Print the value of a JSON string field. Arguments: input, field name (a regex).
json_field() {
  printf '%s' "$1" | sed -nE "s/.*\"($2)\"[[:space:]]*:[[:space:]]*\"(([^\"\\\\]|\\\\.)*)\".*/\\2/p"
}

# Make a path relative to the project folder.
rel_path() {
  local p=$1
  printf '%s' "${p#"${CLAUDE_PROJECT_DIR:-$PWD}"/}"
}

is_test_path() {
  printf '%s' "$1" | grep -qE "$TEST_RE"
}

is_guard_path() {
  printf '%s' "$1" | grep -qE "$GUARD_RE"
}
