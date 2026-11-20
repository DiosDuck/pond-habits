# ADR 0019: AI-assisted workflow

Status: Accepted (2026-11-20)

## Context

The owner wants to learn to work with an AI agent. Claude Code writes most of the code. The owner reviews each PR. The owner writes some features to learn.

## Decision

- Claude runs only locally and interactively. No AI step runs in CI.
- Rules must be enforced by tools, not only by instructions.
- Claude makes branches and commits. Only the owner pushes (`git push` over SSH).
- No GitHub CLI and no GitHub API scripts. The owner opens PRs, merges and changes settings in the GitHub web UI.
- The owner gives CI failures to Claude. Claude runs `make check` before each push.

## Claude Code setup

| Item | Type | Purpose |
|---|---|---|
| `/explain` | skill | Explain a file or a PR (PHP or React) at the owner's level |
| `/feature` | skill | Scenario doc, tests, owner approval, implementation, `make check`, PR text with "Concepts" and ADR checkbox for the owner to paste |
| `/adr` | skill | Write or update an ADR and the privacy deviations |
| `/scenario` | skill | Add a scenario ID to the docs and an empty test with the event list |
| Test protection | PreToolUse hook | Block edits and deletes of existing tests |
| Git protection | PreToolUse hook | Block every `git push` by Claude |
| Done check | Stop hook | Run `make check` before Claude reports "done" |
| `CLAUDE.md`, `.claude/rules/*.md` | docs | Short rules, 100 lines or less each |

## Learning documents

- `docs/learning/react-for-angular-devs.md`
- `docs/learning/laravel-for-symfony-devs.md`
