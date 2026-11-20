# Guardrail files

- Do not change `.claude/settings.json`, `.claude/settings.local.json`, `.claude/hooks/` or `.githooks/`. A hook blocks it.
- To change a guardrail, write the change as a patch file or as text. The owner applies it.
- Test the guard hooks with `bash .claude/hooks/selftest.sh`.
- The Stop hook runs `make check` before you report "done". When it fails, fix it or tell the owner why.
