#!/usr/bin/env bash
# Markdown line limit (ADR 0018): a warning above 100 lines, an error above 150 lines.
# Usage: scripts/md-line-limit.sh [file ...]   (no files: all tracked Markdown files)
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

if [ $# -gt 0 ]; then
  files=("$@")
else
  # Tracked and new (untracked) files, so that a local run sees the same as CI after the commit.
  mapfile -t files < <(git ls-files --cached --others --exclude-standard '*.md')
fi

fail=0
for f in "${files[@]}"; do
  case "$f" in
    *.md) ;;
    *) continue ;;
  esac
  case "$f" in
    CHANGELOG.md | */CHANGELOG.md | docs/generated/*) continue ;;
  esac
  [ -f "$f" ] || continue
  n=$(wc -l < "$f")
  if [ "$n" -gt 150 ]; then
    echo "ERROR: $f has $n lines. The maximum is 150. Split it into a folder with a README.md index."
    fail=1
  elif [ "$n" -gt 100 ]; then
    echo "WARNING: $f has $n lines. The target is 100 or less."
  fi
done
exit "$fail"
