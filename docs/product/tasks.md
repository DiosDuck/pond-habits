# Tasks

## Fields (v1)

| Field | Values |
|---|---|
| Title | text |
| Notes | text, optional |
| Value | credits, default 10 |
| Type | one-time (with a deadline) or recurring |
| Recurrence | daily, weekly on chosen days, monthly on day N, yearly on a date |
| Status | todo, done, missed |

## View

A list grouped by: Overdue, Today, This week, Later.

## Rules

- A task done before its deadline gives its value in credits.
- A missed deadline removes its value in credits.
- A scheduler marks a recurring task as missed at the end of its period, in the user's time zone. Then it gives the penalty.
- Reminders (Web Push, optional): tasks due today, and a recurring task about 2 hours before it is missed.

## Backlog

Board columns, subtasks, labels.
