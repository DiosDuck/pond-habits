# ADR 0018: Documentation rules

Status: Accepted (2026-11-20)

## Decision

- All written output uses ASD-STE100 Simplified Technical English.
- A Markdown file has 100 lines or less. CI fails a file with more than 150 lines.
- A large topic becomes a folder with a `README.md` index and sub-files.
- Exceptions: `CHANGELOG.md` (generated) and `docs/generated/`.
- A link checker in CI checks all relative links.
- Decisions are ADRs in `docs/adr/`.
- Rules and their enforcement are in [rules.md](../rules.md). `CLAUDE.md` stays short and points to it.
- Each documented flow has a scenario ID that a test checks (see [ADR 0008](0008-testing-strategy.md)).
- The PR template has a checkbox: "This PR changes a recorded decision or the privacy baseline". CI checks it.
