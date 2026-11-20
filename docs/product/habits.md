# Habits

## Habit types

| Type | Example | How it works |
|---|---|---|
| Abstain | No smoking | A live counter shows the time since the last relapse. The user presses "I relapsed". The app gives a penalty and resets the counter. The best streak is kept. |
| Achieve | 10k steps | Each day the user ticks "done" or enters a number against a target. A missed day breaks the streak. |

## Rules

- Data entry is manual only. No device integration in v1.
- The day boundary is 00:00 in the user's time zone.
- Reminder (Web Push, optional): a daily habit that is not ticked yet, for example at 21:00.

## Backlog

Automatic data from Google Fit or Apple Health. This needs the native app (Capacitor).
