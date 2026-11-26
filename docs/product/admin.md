# Admin page

Part of [Accounts](accounts.md). The invite form: [invites.md](invites.md).

## Pages

| Page | Content |
|---|---|
| `/admin` | The invite form, and links to "Users" and "Invites". It loads no lists. |
| `/admin/users` | One card for each user: name, chips (Admin, You, Disabled), email, join date. "Disable" or "Enable". |
| `/admin/invites` | Pending invites: address, expiry date, "Cancel invite". Empty: "No pending invites." |

## Rules

- Only an admin can open these pages. The API checks the role for each request and returns 403 to a user who is not an admin.
- "Disable" asks first: "Disable <name>?" with the effects. The action runs only after "Disable" in that dialog.
- A disabled user is logged out on all devices at once (the app ends all sessions). The user cannot log in or reset the password. The data stays.
- "Enable" and "Cancel invite" run at once. They are easy to undo.
- An admin cannot disable the own account: "You cannot disable your own account." Other admins can be disabled.
- To send an invite again, use the invite form: the new link replaces the old one.
- An admin cannot delete another user. A user deletes the own account in Settings.
- Domain events have IDs only, never an email address.

## Scenarios

### SCN-AUTH-ADMIN-004: Disable a user who has a session
Events: `admin.user_disabled`, `auth.sessions_ended`

1. The admin chooses "Disable" on a user card and confirms in the dialog.
2. The app marks the user as disabled and ends the user's sessions on all devices.

### SCN-AUTH-ADMIN-005: Enable a disabled user
Events: `admin.user_enabled`

1. The admin chooses "Enable" on the card of a disabled user.
2. The user can log in again.

### SCN-AUTH-ADMIN-006: An admin tries to disable the own account
Events: `admin.action_refused`

1. An admin sends a disable request for the own account.
2. The app shows "You cannot disable your own account." Nothing changes.

### SCN-AUTH-ADMIN-007: Cancel a pending invite
Events: `admin.invite_cancelled`

1. The admin chooses "Cancel invite" on the Invites page.
2. The app deletes the invite. The link in the mail stops working.
