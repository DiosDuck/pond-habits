# Settings page

Part of [Accounts](accounts.md). Privacy: [privacy.md](../privacy.md), items 1, 2 and 14.

## Sections

| Section | Content |
|---|---|
| Profile | Name and time zone, one "Save". A button "Use this device's time zone". |
| Theme | System, Light or Dark. It changes at once and is saved on the account, so it follows the user to each device. |
| Password | Current password, new password, repeat. |
| Your data | "Download my data": a JSON file. |
| Delete account | "Delete my account", with a confirm that asks for the password. |

## Rules

- The day starts at 00:00 in the time zone of the user.
- A new password has the same rules as at registration. After the change the app ends **all** sessions of the user, this device too. The user logs in again with the new password.
- The export has the data of the user only, never data of other persons. Each feature adds its own section to the export.
- Delete account: the app deletes the user and all data of the user at once, ends all sessions and opens the login page. It cannot be undone.
- The only active admin cannot delete the own account: "You are the only admin. Another admin must exist first."
- A user who cannot log in (disabled) asks the operator. The operator uses `make export-user EMAIL=…` and `make delete-user EMAIL=…`. The commands use the same code as this page.
- Later: change of the email address (with a verification mail), reminder switches (Phase 5, Web Push).
- Domain events have IDs only, never an email address.

## Scenarios

### SCN-SET-001: Change the name, the time zone or the theme
Events: `user.settings_updated`

1. The user changes the name and the time zone and chooses "Save", or chooses a theme.
2. The app saves the change.

### SCN-SET-002: Change the password
Events: `auth.password_changed`, `auth.sessions_ended`

1. The user enters the current password and a new password two times.
2. The app saves the new password, ends all sessions of the user and opens the login page.

### SCN-SET-003: Change the password with a wrong current password
Events: `auth.password_change_refused`

1. The current password is not correct.
2. The app shows "The current password is not correct." Nothing changes.

### SCN-SET-004: Download the own data
Events: `user.data_exported`

1. The user chooses "Download my data".
2. The browser saves a JSON file with the data of the user.

### SCN-SET-005: Delete the own account
Events: `user.deleted`

1. The user chooses "Delete my account" and enters the password in the confirm.
2. The app deletes the account and all its data, ends all sessions and opens the login page.

### SCN-SET-006: Delete the account with a wrong password
Events: `user.delete_refused`

1. The password in the confirm is not correct.
2. The app shows "The password is not correct." Nothing is deleted.

### SCN-SET-007: The only admin tries to delete the own account
Events: `user.delete_refused`

1. No other active admin exists.
2. The app shows "You are the only admin. Another admin must exist first." Nothing is deleted.

### SCN-SET-008: The operator exports and deletes the data of a user
Events: `user.data_exported`, `user.deleted`

1. The operator runs `make export-user EMAIL=…`, then `make delete-user EMAIL=…`.
2. The app writes the export file, then deletes the user and all data of the user.
