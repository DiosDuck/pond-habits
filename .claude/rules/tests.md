# Tests

- Do not edit or delete an existing test file. A hook blocks it. Add a new test file.
- Test paths: `tests/`, `test/`, `e2e/`, `__tests__/`, `*.test.*`, `*.spec.*`, `*Test.php`.
- Write the tests first. The owner approves them before the implementation starts (ADR 0008).
- When an existing test is wrong, stop and tell the owner. Only the owner adds the label `tests-change-approved`.
- Each documented scenario (`SCN-*`) has a test with the same ID and event list.
