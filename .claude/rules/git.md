# Git workflow

- Make a short-lived branch from `main`: `feat/`, `fix/`, `chore/` or `docs/` (ADR 0007).
- Commit messages follow Conventional Commits.
- Do not run `git push`. A hook blocks it. Give the owner the exact push command, for example `! git push -u origin <branch>`.
- Do not use `gh` or GitHub API scripts (ADR 0019). The owner opens PRs, merges and changes settings in the GitHub web UI.
- After the push, write the PR title and body in chat. The body has a "Concepts" section and the ADR checkbox.
- Run `make check` before each push. When CI fails, the owner gives you the job name and the error lines.
- Do not skip the git hooks (`--no-verify`, `commit -n`, `core.hooksPath`). A hook blocks it.
- Do not rewrite history. Do not force push.
