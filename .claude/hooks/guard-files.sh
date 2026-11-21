#!/usr/bin/env bash
# Claude Code PreToolUse hook for Edit, Write, MultiEdit and NotebookEdit.
# Blocks:
#   - all changes to guardrail files (.claude/settings*.json, .claude/hooks/, .githooks/)
#   - changes to test files that exist. A new test file is allowed.
# Exit code 2 blocks the tool call. Claude gets the message on stderr.
set -u
. "$(dirname "$0")/lib.sh"

input=$(read_input)
path=$(json_field "$input" 'file_path|notebook_path')
[ -z "$path" ] && exit 0
rel=$(rel_path "$path")

if is_guard_path "$rel"; then
  block "'$rel' is a guardrail file. Only the owner changes it. Write the change as text for the owner."
fi

if is_test_path "$rel" && is_committed "$rel"; then
  block "'$rel' is a committed test. Do not change it (ADR 0008). Add a new test file, or ask the owner."
fi

exit 0
