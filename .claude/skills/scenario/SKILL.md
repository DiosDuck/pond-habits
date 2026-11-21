---
name: scenario
description: Add a documented scenario (SCN-* ID and expected domain events) to docs/product/ and an empty feature test with the same ID and event list. Use when a flow needs a scenario, when the /feature skill asks for one, or when the owner runs /scenario.
---

# /scenario

Each documented flow has a scenario ID and an expected list of domain events (ADR 0008).
`scripts/docs-check.sh` (`make lint-repo`) fails when the document and the test do not agree.
The format is in `docs/product/README.md`, section "Scenario format".

## Steps

1. **Find the area and the next ID.** The ID is `SCN-<AREA>-<NNN>`, for example `SCN-POMO-001`.
   An area can have more parts: `SCN-AUTH-ADMIN-001`. Search for the highest number in use:
   `grep -rhoE 'SCN-<AREA>-[0-9]{3}' docs/product backend/tests` and add 1.
2. **Agree on the event names with the owner.** Names are `<area>.<past-tense verb>`, for example
   `pomodoro.completed`. Context has IDs only, never personal data (ADR 0023).
3. **Add the scenario to the document** `docs/product/<area>.md`:

   ```markdown
   ### SCN-POMO-001: Complete a focus session
   Events: `pomodoro.started`, `pomodoro.completed`

   1. The user starts a focus session.
   2. The timer reaches zero.
   ```

4. **Add a new feature test file** `backend/tests/Feature/<Area>/<Name>Test.php`. Do not add the test
   to an existing committed file: the guard hook blocks that, and it is correct to block it.
   Keep the `scenario(...)` call on one line:

   ```php
   it('SCN-POMO-001 completes a focus session', function (): void {
       $scenario = scenario('SCN-POMO-001', ['pomodoro.started', 'pomodoro.completed']);

       // Arrange and act: call the API like the frontend does.

       $scenario->assertEvents();
   })->todo();
   ```

   Use `->todo()` until the owner approves the test. Then remove `->todo()` and write the real steps.
5. **Run** `make lint-repo`. `docs-check` must pass.
6. **Show the owner** the scenario text and the test. Wait for approval (ADR 0008: tests first).

## Rules

- One scenario for each user-visible flow, plus one for each important error path.
- Never change a committed test. When a committed scenario must change, stop and ask the owner.
