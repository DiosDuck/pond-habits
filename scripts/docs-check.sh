#!/usr/bin/env bash
# Scenario check (ADR 0008). It compares the scenario documents with the tests:
#   - each scenario ID in docs/product/ has a test with the same ID
#   - each test with a scenario ID has a scenario in docs/product/
#   - the event lists are the same, in the same order
#
# Format in a document (docs/product/*.md):
#   ### SCN-POMO-001: Complete a focus session
#   Events: `pomodoro.started`, `pomodoro.completed`
#
# Format in a test (PHP or TypeScript, on one line):
#   scenario('SCN-POMO-001', ['pomodoro.started', 'pomodoro.completed'])
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

ID_RE='SCN-[A-Z]+(-[A-Z]+)*-[0-9]{3}'
docs=$(mktemp)
tests=$(mktemp)
trap 'rm -f "$docs" "$tests"' EXIT

# Documents: "ID|event1,event2". The Events: line must come after the heading with the ID.
for f in $(git ls-files 'docs/product/*.md' 'docs/product/**/*.md'); do
  awk -v re="$ID_RE" '
    /^[[:space:]]*```/ { code = !code; next }
    code { next }
    /^#+ / { id = ""; if (match($0, re)) id = substr($0, RSTART, RLENGTH) }
    id != "" && /^Events:/ {
      line = $0; sub(/^Events:[[:space:]]*/, "", line); gsub(/[`[:space:]]/, "", line)
      print id "|" line; id = ""
    }' "$f"
done | sort > "$docs"

# Tests: "ID|event1,event2".
git ls-files 'backend/tests/*' 'frontend/src/*' 'frontend/e2e/*' |
  xargs -r grep -ohE "scenario\(['\"]${ID_RE}['\"],[[:space:]]*\[[^]]*\]" 2> /dev/null |
  sed -E "s/^scenario\(['\"]([^'\"]+)['\"],[[:space:]]*\[(.*)\]$/\1|\2/; s/['\"[:space:]]//g; s/,$//" |
  sort > "$tests" || true

fail=0
for id in $(cut -d'|' -f1 "$docs" | sort -u); do
  grep -q "^$id|" "$tests" || { echo "ERROR: $id is in docs/product/ but no test has it."; fail=1; }
done
for id in $(cut -d'|' -f1 "$tests" | sort -u); do
  grep -q "^$id|" "$docs" || { echo "ERROR: a test has $id, but docs/product/ has no such scenario."; fail=1; }
done
while IFS='|' read -r id events; do
  test_events=$(grep "^$id|" "$tests" | head -1 | cut -d'|' -f2)
  if [ -n "$test_events" ] && [ "$test_events" != "$events" ]; then
    echo "ERROR: $id: the events differ. Document: $events. Test: $test_events."
    fail=1
  fi
done < "$docs"

[ "$fail" = 0 ] && echo "docs-check: $(wc -l < "$docs") scenarios, all have matching tests."
exit "$fail"
