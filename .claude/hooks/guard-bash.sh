#!/usr/bin/env bash
# Claude Code PreToolUse hook for the Bash tool.
# Blocks:
#   - every "git push" (only the owner pushes, ADR 0019)
#   - git hook bypass: --no-verify, "commit -n", core.hooksPath
#   - commands that change or delete existing tests or guardrail files
# This is a best-effort text check. The CI job test-guard is the second line.
# Exit code 2 blocks the tool call. Claude gets the message on stderr.
set -u
. "$(dirname "$0")/lib.sh"

input=$(read_input)
cmd=$(json_field "$input" 'command')
[ -z "$cmd" ] && exit 0

# Undo the JSON escapes. A newline becomes a command separator.
cmd=$(printf '%s' "$cmd" | sed -E 's/\\n/;/g; s/\\t/ /g; s/\\"/"/g; s/\\\\/\\/g')
cd "${CLAUDE_PROJECT_DIR:-$PWD}" 2>/dev/null || true

has() { printf '%s' "$1" | grep -qE -- "$2"; }

# Check one path for protection. Arguments: path, reason text.
check_path() {
  local tok=$1
  tok=${tok//\"/}
  tok=${tok//\'/}
  [ -z "$tok" ] && return 0
  local rel
  rel=$(rel_path "$tok")
  if is_guard_path "$rel"; then
    block "the command changes the guardrail file '$rel'. Only the owner changes it."
  fi
  if is_test_path "$rel"; then
    case "$tok" in
      *'*'* | *'?'*) block "the command can change existing tests ('$rel'). Do not change tests (ADR 0008)." ;;
    esac
    if [ -e "$tok" ]; then
      block "the command changes the existing test '$rel'. Do not change tests (ADR 0008)."
    fi
  fi
}

WORD='(^|[[:space:]])'
END='([[:space:]]|$)'
MUTATE="${WORD}(rm|mv|truncate|tee|dd|ln|chmod|unlink|shred)${END}|sed[[:space:]]+(-[a-zA-Z]*i|--in-place)|perl[[:space:]]+-[a-zA-Z]*i|git[[:space:]]+(rm|mv|checkout|restore)${END}"

while IFS= read -r seg; do
  [ -z "${seg// /}" ] && continue

  # For the git checks, ignore quoted text (for example a commit message).
  bare=$(printf '%s' "$seg" | sed -E "s/\"[^\"]*\"//g; s/'[^']*'//g")
  if has "$bare" "${WORD}git${END}"; then
    if has "$bare" "${WORD}push${END}"; then
      block "only the owner pushes. Give the owner the command: ! git push -u origin <branch>"
    fi
    if has "$bare" '--no-verify|core\.hooksPath'; then
      block "do not skip or change the git hooks."
    fi
    if has "$bare" "${WORD}commit${END}" && has "$bare" "${WORD}-[a-zA-Z]*n[a-zA-Z]*${END}"; then
      block "'git commit -n' skips the git hooks. Do not use it."
    fi
  fi

  # Redirect targets: "> file" and ">> file".
  while IFS= read -r target; do
    check_path "$(printf '%s' "$target" | sed -E 's/^>>?[[:space:]]*//')"
  done < <(printf '%s' "$seg" | grep -oE '>>?[[:space:]]*[^[:space:]&>]+' || true)

  # cp and install change only the last path.
  if has "$seg" "${WORD}(cp|install)${END}"; then
    read -ra words <<< "$seg"
    check_path "${words[${#words[@]}-1]}"
  fi

  if has "$seg" "$MUTATE"; then
    read -ra words <<< "$seg"
    for w in "${words[@]}"; do
      check_path "$w"
    done
  fi
done < <(printf '%s\n' "$cmd" | tr ';&|' '\n\n\n')

exit 0
