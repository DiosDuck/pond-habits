# Password reset

Part of [Accounts](accounts.md). Decisions: [ADR 0005](../adr/0005-auth-and-registration.md). The link opens the set-password page of [login.md](login.md).

## Rules

- The login page has the link "Forgot your password?".
- The answer is always the same: "If an account exists for this address, we sent a link." It does not tell if the account exists.
- No mail for an address with no account, or for a disabled account.
- The link has the token only. It opens `/set-password?token=…`. It works once and expires (`config/auth.php`, `passwords.users.expire`).
- Limits (`config/auth.php`, `reset`):
  - for each address: a number of links for each hour. Over the limit, no mail, but the same answer;
  - for each IP address: a number of requests for each hour. Over the limit, status 429.
- A new password ends all other sessions of the user. The event `auth.sessions_ended` is recorded only when at least one session ended.
- Domain events have IDs only, never an email address.

## Scenarios

### SCN-AUTH-011: Request a reset link
Events: `auth.reset_requested`, `auth.password_link_sent`

1. The user enters the email address of an active account.
2. The app mails a reset link and shows the general answer.

### SCN-AUTH-012: Request a reset link for an address with no account
Events: `auth.reset_requested`

1. The user enters an address that has no account.
2. The app shows the general answer. No mail goes out.

### SCN-AUTH-013: The limit for one address is reached
Events: `auth.reset_requested`, `auth.reset_limited`

1. The limit is set to 1 link for this scenario. One link went out already.
2. The user asks again. The app shows the general answer. No mail goes out.

### SCN-AUTH-014: Set a new password from the reset link
Events: `auth.password_set`, `auth.sessions_ended`

1. The user has a session on another device.
2. The user sets a new password from the link. The app ends the other sessions.
