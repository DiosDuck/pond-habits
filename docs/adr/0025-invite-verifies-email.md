# ADR 0025: The invite link verifies the email

Status: Accepted (2026-11-26). It changes one point of [ADR 0005](0005-auth-and-registration.md): the separate verification mail after an invite.

## Context

ADR 0005 lists the flow "invite, register, verify email, login". The invite mail goes to the address, and the registration needs the token from that mail. Only the owner of the inbox has the token. A second mail would prove the same thing again.

## Decision

- Registration from an invite marks the email as verified. No verification mail is sent.
- The registration form does not have an email input. The server takes the address from the invite.
- After registration the user is logged in at once.
- The invite link has the token only (no address in the URL). The server stores a hash of the token.
- The link works once and expires after 7 days. A new invite to the same address replaces the old one.
- Each admin has an invite limit (`config/auth.php`, `invites`). The global daily mail cap of ADR 0005 comes in Phase 4 with the mail provider.

## Alternatives

- Verification mail after registration (ADR 0005 as written): one more mail and one more step, with no more proof.

## Consequences

- A verification mail is still necessary later for open sign-up and for a change of the email address.
- A pending invite stores the invited address. The app deletes the invite when it is used, and a daily job deletes expired invites ([privacy.md](../privacy.md), item 13).
