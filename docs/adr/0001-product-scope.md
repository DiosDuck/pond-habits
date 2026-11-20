# ADR 0001: Product scope and users

Status: Accepted (2026-11-20)

## Context

The owner wants one app for daily tracking: pomodoro, nutrition, habits and tasks. A credits system rewards and punishes the user. The user spends credits on rewards that the user defines.

## Decision

- The app is multi-user, for about 10 users. Every record has a `user_id`.
- Registration is by invite only. Open sign-up comes later, behind an admin setting.
- The app has no social features. Each user sees only the user's own data.
- The UI is in English. i18n keys are used from day 1.
- Data entry is manual. No device or food database integration in v1.
- Feature order: Pomodoro, Credits, Rewards, Tasks, Habits, Nutrition.

## Consequences

- Real auth, mail and data isolation are necessary from the start.
- A migration from single-user to multi-user is not necessary later.
- Details: [product/README.md](../product/README.md).
