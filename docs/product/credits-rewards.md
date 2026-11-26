# Credits and rewards

## Credits ledger

- Credits are stored in an append-only ledger. Each change is one row: amount, reason, source event, time.
- The balance is the sum of the rows.
- Do not edit or delete a row. To fix a mistake, add a correction row.
- Only the server calculates credits.
- The balance can be negative. With a negative balance, the user cannot buy rewards.
- The user can see the full credits history.

## Default credit values

The user can change each value per item.

| Event | Default |
|---|---|
| Pomodoro completed | +5 |
| Break skipped | +1 |
| Break overrun (grace time over) | -X (user setting) |
| Task done before its deadline | +task value (default 10) |
| Task deadline missed, or recurring task missed at period end | -task value |
| Achieve habit kept for the day | +5, and a bonus every 7 days of streak |
| Abstain habit broken | -20, and the streak resets |
| Daily nutrition goals met | +10 |
| Nutrition limit exceeded | -5 |

## Rewards

- The user creates rewards: name, price, icon.
- The icon comes from a built-in set of Material Symbols (coin, gift, coffee, game, film, food, travel and more). The default icon is the coin (`toll`). See [ADR 0024](../adr/0024-visual-design.md).
- The coin icon is also the icon of the credits currency.
- No image uploads.
- A reward is repeatable by default. The user can mark a reward as "one-time".
- A purchase deducts credits and adds a row to the purchase history.
