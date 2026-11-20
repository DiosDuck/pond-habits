#!/usr/bin/env bash
# Relative link check (ADR 0018): every relative link in a tracked Markdown file must resolve.
# Links into local/ are errors: local/ is private and is not in the repo (ADR 0023).
# Web links (http, https, mailto) and links inside code blocks are not checked.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

fail=0
while IFS= read -r f; do
  dir=$(dirname "$f")
  while IFS= read -r link; do
    target=${link%%#*}
    target=${target%% *}
    [ -z "$target" ] && continue
    case "$target" in
      http://* | https://* | mailto:*) continue ;;
    esac
    if [ "${target:0:1}" = / ]; then path=".$target"; else path="$dir/$target"; fi
    case "$(realpath -m --relative-to=. "$path")" in
      local | local/*)
        echo "ERROR: $f: link into the private local/ folder: $link"
        fail=1
        continue
        ;;
    esac
    if [ ! -e "$path" ]; then
      echo "ERROR: $f: broken link: $link"
      fail=1
    fi
  done < <(awk '/^[[:space:]]*```/ { code = !code; next } !code' "$f" |
    grep -oE '\]\([^)]+\)' | sed -E 's/^\]\((.*)\)$/\1/' || true)
done < <(git ls-files --cached --others --exclude-standard '*.md' | grep -v '^docs/generated/')
exit "$fail"
