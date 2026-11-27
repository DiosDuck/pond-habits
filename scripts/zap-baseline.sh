#!/usr/bin/env bash
# OWASP ZAP baseline scan (ADR 0015): ZAP visits the site like a visitor for a short time and
# reports problems. It is passive: it reads the answers and sends no attacks.
# The scan fails when ZAP finds a high-risk problem. Lower risks are listed, not failed.
# Usage: scripts/zap-baseline.sh <url>
# Environment: ZAP_IMAGE (required); ZAP_BASIC_AUTH="user:password" (optional, for staging).
# The report is not uploaded: the repository and its Actions artifacts are public (ADR 0023).
set -euo pipefail

target=${1:?usage: zap-baseline.sh <url>}
: "${ZAP_IMAGE:?set ZAP_IMAGE}"
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT
chmod 777 "$work" # ZAP runs as its own user in the container

auth=()
if [ -n "${ZAP_BASIC_AUTH:-}" ]; then
  header="Basic $(printf '%s' "$ZAP_BASIC_AUTH" | base64 -w 0)"
  # GitHub hides secrets in the log, but not a value made from them: hide it too.
  [ -n "${GITHUB_ACTIONS:-}" ] && echo "::add-mask::$header"
  # Send the header only to the scanned host. ZAP wants the host name without the port.
  host=${target#*://}
  host=${host%%/*}
  auth=(-e ZAP_AUTH_HEADER=Authorization -e "ZAP_AUTH_HEADER_VALUE=$header" -e "ZAP_AUTH_HEADER_SITE=${host%%:*}")
fi

# -I: warnings do not fail the scan here; this script decides from the risk level below.
# -m 2: spider for 2 minutes at most.
status=0
docker run --rm --network host -v "$work":/zap/wrk:rw "${auth[@]}" "$ZAP_IMAGE" \
  zap-baseline.py -t "$target" -J report.json -I -m 2 > "$work/zap.log" 2>&1 || status=$?
if [ ! -s "$work/report.json" ]; then
  echo "zap-baseline: ZAP did not finish (exit $status). Last lines:" >&2
  tail -n 30 "$work/zap.log" >&2
  exit 1
fi

# One line for each alert: risk, rule ID, name, number of URLs. Risk codes: 3 high, 2 medium,
# 1 low, 0 info. .zap/ignore.tsv lists reviewed rules (ID, reason): they never fail the scan.
python3 - "$work/report.json" "$(git rev-parse --show-toplevel)/.zap/ignore.tsv" <<'EOF'
import json, os, sys

names = {"3": "HIGH", "2": "Medium", "1": "Low", "0": "Info"}
ignored = {}
for row in open(sys.argv[2]):
    if row.strip() and not row.startswith("#"):
        rule, reason = row.rstrip("\n").split("\t", 1)
        ignored[rule] = reason
alerts = []
for site in json.load(open(sys.argv[1])).get("site", []):
    for alert in site.get("alerts", []):
        alerts.append((alert["riskcode"], alert["pluginid"], alert["alert"], len(alert.get("instances", []))))
alerts.sort(key=lambda a: (-int(a[0]), a[2]))

lines = []
for risk, rule, name, count in alerts:
    note = f"  [ignored: {ignored[rule]}]" if rule in ignored else ""
    lines.append(f"{names[risk]:<7} {rule:>6} {name} ({count} URLs){note}")
lines = lines or ["No alerts."]
high = sum(1 for risk, rule, _, _ in alerts if risk == "3" and rule not in ignored)
print("\n".join(lines))

summary = os.environ.get("GITHUB_STEP_SUMMARY")
if summary:
    with open(summary, "a") as f:
        f.write("## ZAP baseline scan\n\n```\n" + "\n".join(lines) + "\n```\n")

if high:
    print(f"zap-baseline: {high} high-risk alert(s). Fix them, or explain them in .zap/ignore.tsv.", file=sys.stderr)
    sys.exit(1)
print("zap-baseline: no high-risk alerts")
EOF
