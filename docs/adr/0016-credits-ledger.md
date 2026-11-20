# ADR 0016: Credits ledger

Status: Accepted (2026-11-20)

## Decision

- Credits are an append-only ledger. One row for each change: amount, reason, source event, time.
- The balance is the sum of the rows.
- Rows are never edited or deleted. A correction is a new row.
- Only the server calculates credits. The client never sends an amount.
- The balance can be negative. A user with a negative balance cannot buy rewards.
- The user can change the credit value for each item. The defaults are in [credits-rewards.md](../product/credits-rewards.md).

## Consequences

- The credits history page comes from the ledger at no extra cost.
- A later offline outbox cannot create wrong balances, because the server checks each event.
