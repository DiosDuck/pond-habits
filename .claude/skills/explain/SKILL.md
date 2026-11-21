---
name: explain
description: Explain a file, a folder, a branch or a pull request of this repo at the owner's level (PHP expert, learning Laravel, React and deploy pipelines). Use when the owner asks "explain", "what does this do", "walk me through" or runs /explain.
---

# /explain

Explain code so that the owner can review it with confidence (ADR 0019).

## The owner

- Expert in PHP with Symfony and Laminas. Knows Laravel a little and Angular at a basic level.
- Learns React, TypeScript tooling, Docker, CI/CD and infrastructure for the first time.
- Wants plain words, and wants to know the cost and the security effect first.

## Find the target

The argument can be:

- A file or a folder: read it.
- A branch name: `git diff origin/main...<branch>`.
- A PR number: the repo is public, so fetch it over HTTPS with no login:
  `git fetch origin pull/<number>/head:pr-<number>`, then `git diff origin/main...pr-<number>`.
  Do not use `gh` or the GitHub API (ADR 0019).
- No argument: the changes on the current branch, `git diff origin/main...HEAD`, and the uncommitted changes.

## Write the explanation

Use ICAO-style chat phrasing and ASD-STE100 for longer text (CLAUDE.md). Use this order:

1. **Purpose**: one or two sentences. What does this code do, and why does the app need it?
2. **Cost and risk**: does it cost money? Does it change security, privacy (ADR 0023) or data? Say "none" if none.
3. **Walk-through**: go through the code in reading order. Quote short parts with `file:line`.
4. **Map to what the owner knows**: for each new idea, give the nearest Symfony, Laminas or Angular idea.
   For example: service container binding = Symfony service definition; React hook = no direct
   Angular match, nearest is a component field plus lifecycle method; TanStack Query = Angular service with an RxJS cache.
5. **What to check in review**: 3 to 5 points where a mistake is most likely.
6. **Read more**: one or two official links (react.dev, laravel.com/docs, docs.docker.com and similar).

## Rules

- Do not change any file. This skill only reads.
- When a part is unclear or looks wrong, say so. Do not guess.
- Keep it short. Explain more only when the owner asks.
