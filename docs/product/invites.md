# Invites and registration

Part of [Accounts](accounts.md). Decisions: [ADR 0005](../adr/0005-auth-and-registration.md), [ADR 0025](../adr/0025-invite-verifies-email.md).

## Rules

- Only an admin can send an invite. The API checks the role for each admin request.
- An invite to an address that has an account is refused: "This email address has an account already."
- A new invite to the same address replaces the old one. The old link stops working.
- The invite link has the token only. The link works once and expires after 7 days.
- The registration form shows the invited address, read-only. The address is not an input: the server takes it from the invite.
- Fields: name, password (12 characters or more, not in a known data breach), repeat. The time zone comes from the browser.
- The invite link counts as email verification. After registration the user is logged in and sees Today.
- Invites have a limit for each admin (`config/auth.php`, `invites`).
- The app deletes an invite when it is used. A daily job deletes expired invites.
- Domain events have IDs only, never an email address.

## Scenarios

### SCN-AUTH-ADMIN-001: An admin sends an invite
Events: `invite.sent`

1. The admin enters an email address on the Admin page and sends the invite.
2. The app mails a registration link to the address.

### SCN-AUTH-ADMIN-002: A user who is not an admin cannot send an invite
Events: `auth.admin_denied`

1. A logged-in user without the admin role calls the invite API.
2. The API returns status 403.

### SCN-AUTH-ADMIN-003: Invite to an address that has an account
Events: `invite.refused`

1. The admin enters the address of an existing account.
2. The app shows "This email address has an account already." No mail goes out.

### SCN-AUTH-009: Register from the invite
Events: `user.registered`, `auth.logged_in`

1. The invitee opens the link and sees the invited address.
2. The invitee enters a name and a password two times.
3. The app makes the account (role user, email verified), deletes the invite and starts a session.

### SCN-AUTH-010: Use an invite link that is not valid
Events: `invite.rejected`

1. The invitee sends the form with a wrong, used or expired token.
2. The app shows "This invite is not valid or has expired." No account is made.
