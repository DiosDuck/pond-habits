#!/usr/bin/env bash
# Self-test for the guard hooks. It sends fake tool calls to the hooks.
# Run: bash .claude/hooks/selftest.sh   (exit code 0 = all cases pass)
set -u
here=$(cd "$(dirname "$0")" && pwd)
fix=$(mktemp -d)
trap 'rm -rf "$fix"' EXIT
mkdir -p "$fix/backend/tests/Feature" "$fix/frontend/src" "$fix/.claude/hooks"
touch "$fix/backend/tests/Feature/HealthTest.php" "$fix/frontend/src/App.test.tsx" "$fix/frontend/src/App.tsx"
# Commit these files. A test is protected only when it is committed.
git -C "$fix" init -q
git -C "$fix" add -A
git -C "$fix" -c user.name=selftest -c user.email=selftest@example.com commit -q -m fixture
# A new test that is not committed yet. Claude can still change it.
touch "$fix/backend/tests/Feature/DraftTest.php"

pass=0
fail=0
# Arguments: expected exit code (0 or 2), hook name, JSON tool input, label.
case_() {
  local want=$1 hook=$2 json=$3 label=$4 got
  printf '%s' "$json" | CLAUDE_PROJECT_DIR="$fix" bash "$here/$hook" 2>/dev/null
  got=$?
  if [ "$got" = "$want" ]; then pass=$((pass + 1)); else fail=$((fail + 1)); echo "FAIL ($got, want $want): $label"; fi
}
b() { case_ "$1" guard-bash.sh "{\"tool_name\":\"Bash\",\"tool_input\":{\"command\":\"$2\",\"description\":\"git push test\"}}" "bash: $2"; }
f() { case_ "$1" guard-files.sh "{\"tool_name\":\"$2\",\"tool_input\":{\"file_path\":\"$fix/$3\"}}" "$2: $3"; }

# Push and hook bypass
b 2 'git push'
b 2 'git push -u origin feat/x'
b 2 'git -C /some/dir push --force'
b 2 'git status && git push'
b 2 'git commit --no-verify -m \"x\"'
b 2 'git commit -n -m \"x\"'
b 2 'git config core.hooksPath /dev/null'
b 0 'git status'
b 0 'git commit -m \"docs: push notes\"'
b 0 'ls -la 2>&1'
# Tests
b 2 'rm backend/tests/Feature/HealthTest.php'
b 2 'rm -rf backend/tests'
b 2 'sed -i s/a/b/ frontend/src/App.test.tsx'
b 2 'echo x > frontend/src/App.test.tsx'
b 2 'git checkout -- backend/tests/Feature/HealthTest.php'
b 2 'rm backend/tests/*'
b 0 'cat backend/tests/Feature/HealthTest.php 2>/dev/null'
b 0 'cp backend/tests/Feature/HealthTest.php /tmp/copy.php'
b 0 'echo x > frontend/src/New.test.tsx'
b 0 'sed -i s/a/b/ frontend/src/App.tsx'
# Guardrail files
b 2 'echo {} > .claude/settings.json'
b 2 'rm .claude/hooks/guard-bash.sh'
b 2 'chmod -x .githooks/pre-push'
# File tools
f 2 Edit backend/tests/Feature/HealthTest.php
f 2 Write frontend/src/App.test.tsx
f 0 Write backend/tests/Feature/NewTest.php
f 0 Edit frontend/src/App.tsx
f 0 Edit backend/tests/Feature/DraftTest.php
b 0 'rm backend/tests/Feature/DraftTest.php'
f 2 Edit .claude/settings.json
f 2 Write .claude/settings.local.json
f 2 Write .claude/hooks/new.sh
f 2 Edit .githooks/pre-commit

echo "selftest: $pass passed, $fail failed"
[ "$fail" = 0 ]
