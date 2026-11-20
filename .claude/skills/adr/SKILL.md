---
name: adr
description: Write a new Architecture Decision Record or update an existing one in docs/adr/, and update the privacy deviations when the privacy baseline changes. Use when a decision is made or changed, when /feature needs it, or when the owner runs /adr.
---

# /adr

An ADR records one decision: the context, the decision and the consequences.
Rules: `docs/adr/README.md` and ADR 0018 (documentation rules).

## Decide: new ADR or change of an existing one

- **A new decision**: write a new ADR with the next number.
- **A changed decision**: the rule in `docs/adr/README.md` is "do not edit an accepted ADR; write a new
  ADR and set the old one to Superseded". Ask the owner which one to do: the owner has sometimes
  allowed a small edit in place. Never edit an accepted ADR without the owner's clear "yes".

## Write a new ADR

1. Find the next number: `ls docs/adr/ | sort | tail -3`.
2. Make `docs/adr/NNNN-<short-name>.md` with this shape:

   ```markdown
   # ADR NNNN: <Title>

   Status: Accepted (<YYYY-MM-DD>)

   ## Context

   <The problem and the facts. Short.>

   ## Decision

   - <What we do.>

   ## Alternatives

   - <Option>: <why not.>

   ## Consequences

   - <What changes, the cost, the risk.>
   ```

3. Add a line to the index table in `docs/adr/README.md`.
4. Superseding an old ADR: change its status line only, to `Status: Superseded by ADR NNNN (<date>)`.
5. Privacy change: add or update the item in `docs/privacy.md`.

## Check

- ASD-STE100 Simplified Technical English. 100 lines or less.
- No personal data, workstation details, live infrastructure values or prices (ADR 0023).
- Run `make lint-repo` (line limit, links, sensitive data).
- In the PR text, tick "This PR changes a recorded decision or the privacy baseline".
