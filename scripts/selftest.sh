#!/usr/bin/env bash
# Self-test for the check scripts: each script must pass good input and fail bad input.
# It works in a temporary git repo. Run: bash scripts/selftest.sh   (exit code 0 = all cases pass)
# The bad sample values are built from parts, so that this file itself passes the sensitive check.
set -uo pipefail
src=$(cd "$(dirname "$0")/.." && pwd)
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
cd "$tmp" || exit 1
git init -q -b main
git config user.name selftest
git config user.email selftest@example.com
mkdir -p scripts .claude/hooks docs/product backend/tests/Feature frontend/src
cp "$src"/scripts/*.sh scripts/
cp "$src"/.claude/hooks/lib.sh .claude/hooks/

pass=0
fail=0
# Arguments: expected exit code (0 or 1), label, command...
expect() {
  local want=$1 label=$2 got
  shift 2
  "$@" > /dev/null 2>&1
  got=$?
  [ "$got" -gt 1 ] && got=1
  if [ "$got" = "$want" ]; then pass=$((pass + 1)); else fail=$((fail + 1)); echo "FAIL ($got, want $want): $label"; fi
}

# --- md-line-limit
seq 1 120 > docs/warn.md
seq 1 151 > docs/long.md
expect 0 "md: 120 lines is only a warning" bash scripts/md-line-limit.sh docs/warn.md
expect 1 "md: 151 lines fails" bash scripts/md-line-limit.sh docs/long.md
rm docs/warn.md docs/long.md

# --- sensitive-check
home="/ho""me/someone/project"
ip="192.168"".1.5"
mail="someone""@mail.io"
printf 'ok: user@example.com noreply@anthropic.com 127.0.0.1 10.0.1\n' > good.txt
expect 0 "sensitive: allowed values pass" bash scripts/sensitive-check.sh good.txt
printf 'path %s\n' "$home" > bad1.txt
expect 1 "sensitive: home path fails" bash scripts/sensitive-check.sh bad1.txt
printf 'ip %s\n' "$ip" > bad2.txt
expect 1 "sensitive: private IP fails" bash scripts/sensitive-check.sh bad2.txt
printf 'mail %s\n' "$mail" > bad3.txt
expect 1 "sensitive: email fails" bash scripts/sensitive-check.sh bad3.txt
printf 'hello Pondsecret\n' > bad4.txt
expect 1 "sensitive: deny-list word fails" env SENSITIVE_DENY_LIST=pondsecret bash scripts/sensitive-check.sh bad4.txt
expect 0 "sensitive: no deny-list, no fail" bash scripts/sensitive-check.sh bad4.txt
rm -f good.txt bad*.txt

# --- commit-msg-check
printf 'feat(pomodoro): add the focus timer\n' > msg1
printf 'added stuff\n' > msg2
expect 0 "commit-msg: conventional message passes" bash scripts/commit-msg-check.sh msg1
expect 1 "commit-msg: free text fails" bash scripts/commit-msg-check.sh msg2
rm msg1 msg2

# --- link-check, docs-check, test-guard and new-deps-check need commits
printf '# Doc\n\n[ok](other.md)\n' > docs/a.md
printf '# Other\n' > docs/other.md
printf '{\n  "dependencies": {\n    "react": "^19"\n  }\n}\n' > frontend/package.json
printf '<?php\n' > backend/tests/Feature/OldTest.php
git add -A && git commit -qm "chore: base"
expect 0 "links: good links pass" bash scripts/link-check.sh
printf '# Doc\n\n[bad](missing.md)\n' > docs/a.md && git add -A
expect 1 "links: broken link fails" bash scripts/link-check.sh
printf '# Doc\n\n[private](../local/notes.md)\n' > docs/a.md && git add -A
expect 1 "links: link into local/ fails" bash scripts/link-check.sh
printf '# Doc\n\n[ok](other.md)\n' > docs/a.md && git add -A

printf '```markdown\n### SCN-DEMO-001: Example only\nEvents: `x.y`\n```\n' > docs/product/format.md
git add -A
expect 0 "docs-check: a scenario inside a code block is only an example" bash scripts/docs-check.sh
printf '### SCN-POMO-001: Focus\nEvents: `pomodoro.started`, `pomodoro.completed`\n' > docs/product/pomo.md
git add -A
expect 1 "docs-check: scenario with no test fails" bash scripts/docs-check.sh
printf "<?php\nscenario('SCN-POMO-001', ['pomodoro.started', 'pomodoro.completed']);\n" > backend/tests/Feature/PomoTest.php
git add -A
expect 0 "docs-check: matching test passes" bash scripts/docs-check.sh
printf "<?php\nscenario('SCN-POMO-001', ['pomodoro.completed']);\n" > backend/tests/Feature/PomoTest.php
git add -A
expect 1 "docs-check: different events fail" bash scripts/docs-check.sh
printf "<?php\nscenario('SCN-POMO-001', ['pomodoro.started', 'pomodoro.completed']);\n" > backend/tests/Feature/PomoTest.php
git add -A
printf "<?php\n/**\n *     scenario('SCN-DOC-001', ['x.y']);\n */\n// scenario('SCN-DOC-002', ['x.y']);\n" > backend/tests/Feature/CommentTest.php
expect 0 "docs-check: a scenario call in a comment is only an example" bash scripts/docs-check.sh
printf "<?php\nscenario('SCN-NEW-001', ['x.y']);\n" > backend/tests/Feature/UntrackedTest.php
expect 1 "docs-check: an untracked test file is checked too" bash scripts/docs-check.sh
rm backend/tests/Feature/CommentTest.php backend/tests/Feature/UntrackedTest.php
git commit -qm "test: scenario"

git switch -qc feat/x
printf '<?php\n// new\n' > backend/tests/Feature/NewTest.php
git add -A && git commit -qm "test: new"
expect 0 "test-guard: a new test passes" bash scripts/test-guard.sh main
printf '<?php\n// changed\n' > backend/tests/Feature/OldTest.php
git add -A && git commit -qm "test: change"
expect 1 "test-guard: a changed test fails" bash scripts/test-guard.sh main
expect 0 "test-guard: a changed test with approval passes" env TESTS_CHANGE_APPROVED=1 bash scripts/test-guard.sh main

printf '{\n  "dependencies": {\n    "react": "^19",\n    "left-pad": "^1"\n  }\n}\n' > frontend/package.json
git add -A && git commit -qm "feat: dep"
expect 0 "deps: local run only lists" bash scripts/new-deps-check.sh main
expect 1 "deps: PR text with no reason fails" env PR_BODY="Summary only" bash scripts/new-deps-check.sh main
expect 0 "deps: PR text with a reason passes" env PR_BODY="## Why these packages
- left-pad: pads strings" bash scripts/new-deps-check.sh main

# --- pre-push-check
z=0000000000000000000000000000000000000000
head=$(git rev-parse HEAD)
old=$(git rev-parse main)
expect 0 "pre-push: new feature branch passes" bash scripts/pre-push-check.sh <<< "refs/heads/feat/x $head refs/heads/feat/x $z"
expect 0 "pre-push: fast-forward passes" bash scripts/pre-push-check.sh <<< "refs/heads/feat/x $head refs/heads/feat/x $old"
expect 1 "pre-push: push to main fails" bash scripts/pre-push-check.sh <<< "refs/heads/main $head refs/heads/main $old"
expect 1 "pre-push: wrong branch name fails" bash scripts/pre-push-check.sh <<< "refs/heads/stuff $head refs/heads/stuff $z"
expect 1 "pre-push: force push fails" bash scripts/pre-push-check.sh <<< "refs/heads/feat/x $old refs/heads/feat/x $head"

echo "scripts selftest: $pass passed, $fail failed"
[ "$fail" = 0 ]
