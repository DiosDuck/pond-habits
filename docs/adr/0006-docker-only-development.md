# ADR 0006: Docker-only development

Status: Accepted (2026-11-20)

## Context

The owner wants to change the work machine without setup. The work machine can be ARM64 or x86_64.

## Decision

- The dev setup needs only `docker` (with compose), `git` and `make` on the host.
- PHP, Composer, Node, npm, linters and all other tools run in containers.
- Git uses SSH with a key that is used only for GitHub. The GitHub CLI (`gh`) is not used.
- `make` is the single entry point: `make up`, `make test`, `make check`, `make admin` and others.
- Git hooks are plain scripts in `.githooks/` (`git config core.hooksPath .githooks`). They call `make` targets.
- The editor is VS Code with Dev Containers. The `.devcontainer/` folder is in the repo.
- CI runs the same `make` targets as the developer.

## Consequences

- Local, CI and production use the same images and the same commands.
- Old local Docker data is removed before the repository is set up. The private list is in `local/` (see [ADR 0023](0023-public-repo-content.md)).
