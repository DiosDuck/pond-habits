# Tests

- Do not edit or delete an existing test file. A hook blocks it. Add a new test file.
- Test paths: `tests/`, `test/`, `e2e/`, `__tests__/`, `*.test.*`, `*.spec.*`, `*Test.php`.
- Write the tests first. The owner approves them before the implementation starts (ADR 0008).
- When an existing test is wrong, stop and tell the owner. Only the owner adds the label `tests-change-approved`.
- Each documented scenario (`SCN-*`) has a test with the same ID and event list.

## Check each test before the test commit

A committed test is frozen, also on its own branch. A fix costs the owner a patch. So:

- Test the final state of the phase, not a temporary one. Read the phase plan: will a later PR change this assumption (login, roles, time zone, offline)?
- Put each "not there" check (`queryBy…`, `.not`, `assertMissing`) after a wait for something that is there on the same page.
- Before you rely on a fake or a mock that is new to you, check its behaviour in the scratchpad (for example: the first matching `Http::fake()` wins).
- Make sure that each test can fail: what wrong code would it pass by mistake?
- Give shared test helpers options from the start (for example a user or a session), because committed helpers are frozen too.
- Playwright `getByLabel()` and `getByText()` match a part of the text: "New password" also finds "Repeat the new password". Use `{ exact: true }` when one label is inside another.
- Pages load on demand (lazy). After a click to another page, wait for its heading before you fill a field: the old page stays on screen until the new one is ready.
