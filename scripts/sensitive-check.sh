#!/usr/bin/env bash
# Sensitive-pattern check (ADR 0023). It fails on:
#   - home paths (/home/<user>/, /Users/<user>/)
#   - private IP addresses (10.x, 172.16-31.x, 192.168.x)
#   - email addresses, except example.com/org/net, demo domains, noreply@ and GitHub no-reply addresses
#   - words from the private deny-list: local/deny-list.txt, or the SENSITIVE_DENY_LIST variable (CI)
# Usage: scripts/sensitive-check.sh [file ...]   (no files: all tracked files)
# The output shows the file, the line and the rule, but never the matched text,
# because CI logs of a public repo are public.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

if [ $# -gt 0 ]; then files=("$@"); else mapfile -t files < <(git ls-files); fi

deny=$(mktemp)
trap 'rm -f "$deny"' EXIT
{
  [ -f local/deny-list.txt ] && cat local/deny-list.txt
  [ -n "${SENSITIVE_DENY_LIST:-}" ] && printf '%s\n' "$SENSITIVE_DENY_LIST"
} | sed -E 's/#.*//; s/^[[:space:]]+//; s/[[:space:]]+$//' | grep -v '^$' > "$deny" || true

HOME_RE='/home/[a-z_][a-z0-9_-]*/|/Users/[A-Za-z][A-Za-z0-9._-]*/'
IP_RE='(^|[^0-9.])(10\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|192\.168\.[0-9]{1,3}\.[0-9]{1,3}|172\.(1[6-9]|2[0-9]|3[01])\.[0-9]{1,3}\.[0-9]{1,3})([^0-9.]|$)'
EMAIL_RE='[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}'
EMAIL_OK_RE='@example\.(com|org|net)$|@([a-z0-9-]+\.)*demo(\.[a-z]+)?$|^noreply@|@users\.noreply\.github\.com$|^git@github\.com$'

fail=0
report() {
  echo "ERROR: $1: $2"
  fail=1
}

for f in "${files[@]}"; do
  [ -f "$f" ] || continue
  case "$f" in
    *composer.lock | *package-lock.json | docs/generated/* | scripts/sensitive-check.sh) continue ;;
  esac
  grep -Iq . "$f" 2> /dev/null || continue # skip binary and empty files

  while IFS=: read -r line _; do report "$f:$line" "home path"; done < <(grep -nE "$HOME_RE" "$f" || true)
  while IFS=: read -r line _; do report "$f:$line" "private IP address"; done < <(grep -nE "$IP_RE" "$f" || true)
  while IFS=: read -r line addr; do
    printf '%s' "$addr" | grep -qiE "$EMAIL_OK_RE" || report "$f:$line" "email address"
  done < <(grep -noE "$EMAIL_RE" "$f" || true)
  if [ -s "$deny" ]; then
    while IFS=: read -r line _; do report "$f:$line" "word from the private deny-list"; done < <(grep -niwF -f "$deny" "$f" || true)
  fi
done
exit "$fail"
