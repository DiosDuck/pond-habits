<?php

declare(strict_types=1);

use App\Support\DomainEvents\DomainEventLog;
use App\Support\DomainEvents\RecordingDomainEventLog;
use Tests\Support\Scenario;

/**
 * Start a documented scenario in a feature test (ADR 0008). Keep the call on one line,
 * because scripts/docs-check.sh reads the ID and the event list from it:
 *
 *     $scenario = scenario('SCN-POMO-001', ['pomodoro.started', 'pomodoro.completed']);
 *     // ... act ...
 *     $scenario->assertEvents();
 *
 * The app then records the domain events in memory, in place of the log channel.
 *
 * @param  list<string>  $events
 */
function scenario(string $id, array $events): Scenario
{
    $log = new RecordingDomainEventLog;
    app()->instance(DomainEventLog::class, $log);

    return new Scenario($id, $events, $log);
}
