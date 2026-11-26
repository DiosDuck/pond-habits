# First admin, set password, login and logout

Part of [Accounts](accounts.md). Decisions: [ADR 0005](../adr/0005-auth-and-registration.md).

## Rules

- Sanctum SPA cookie mode: a server session in PostgreSQL and an httpOnly session cookie. No tokens.
- A session ends after 30 days with no request. Each request starts the 30 days again.
- The login error is always "The email or password is not correct." It does not tell if the account exists or is disabled.
- The set-password link has the token only. The form asks for the email and the new password.
- A set-password link works once. It expires after the time in `config/auth.php` (`passwords.users.expire`).
- New password: 12 characters or more, not in a known data breach (Have I Been Pwned, k-anonymity).
- Login attempts are limited for each email and IP address (`config/auth.php`, `login`).
- Domain events have IDs only, never an email address.

## Scenarios

### SCN-AUTH-001: Create the first admin
Events: `auth.admin_created`, `auth.password_link_sent`

1. The owner runs `make admin` with an email address.
2. The app makes an admin account with no usable password and mails a set-password link.

### SCN-AUTH-002: Set the password from the link
Events: `auth.password_set`

1. The user opens the link and enters the email and a new password two times.
2. The app saves the password and marks the email as verified.

### SCN-AUTH-008: Use a set-password link that is not valid
Events: `auth.password_link_rejected`

1. The user sends the form with a wrong, used or expired token, or with a different email.
2. The app shows "This link is not valid or has expired." The password does not change.

### SCN-AUTH-003: Log in
Events: `auth.logged_in`

1. The user enters the email and the password.
2. The app starts a session. `GET /api/me` returns the user.

### SCN-AUTH-004: Log in with a wrong password
Events: `auth.login_failed`

1. The user enters a wrong password.
2. The app shows "The email or password is not correct." No session starts.

### SCN-AUTH-005: Too many login attempts
Events: `auth.login_failed`, `auth.login_failed`, `auth.login_failed`, `auth.login_throttled`

1. The limit is set to 3 attempts for this scenario.
2. The user enters a wrong password 3 times. The next attempt gets status 429, also with the correct password.

### SCN-AUTH-006: A disabled user cannot log in
Events: `auth.login_refused`

1. An admin has disabled the account. The user enters the correct password.
2. The app shows "The email or password is not correct." No session starts.

### SCN-AUTH-007: Log out
Events: `auth.logged_out`

1. The logged-in user chooses "Log out".
2. The app ends the session. `GET /api/me` returns status 401.
