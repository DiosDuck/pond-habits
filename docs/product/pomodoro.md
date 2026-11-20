# Pomodoro

## Settings (per user)

| Setting | Default |
|---|---|
| Focus length | 25 min |
| Short break | 5 min |
| Long break | 15 min |
| Focus sessions before a long break | 4 |
| Number of cycles in a run | No limit (or N) |
| Break grace time | 2 min |

The user can save presets, for example "Classic 25/5" and "Deep 50/10".

## Flow

1. The user starts a focus session. It can link to a task (optional).
2. When focus ends, the break starts automatically.
3. When the break ends, the app asks "Back to focus?".
4. The user must confirm within the grace time.
5. If the user does not confirm in time, the app gives a penalty and records the overrun (for example "break overrun: 7 min"). The next focus waits for the user.

## Skip and abandon

| Action | Result |
|---|---|
| Skip a break | Focus starts at once. Small bonus (default +1). |
| Abandon a focus session | No credits. No penalty. |
| Complete a focus session | Credits (default +5). |

## Alerts

At the end of each focus session and each break:

- sound,
- vibration (if the device supports it),
- a visual alert on the screen,
- a Web Push notification when the app is in the background.

## Technical rules

- Mobile browsers pause JavaScript when the screen locks. The timer stores its end time, not a countdown.
- The timer works offline. The server records the start and end of each session.
- The server calculates all credits.
