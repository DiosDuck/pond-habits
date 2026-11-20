<?php

declare(strict_types=1);

namespace App\Support\DomainEvents;

/**
 * Records domain events, for example "pomodoro.completed" (ADR 0008).
 *
 * Scenario tests compare the recorded event names with the event list in the scenario document.
 * The context must not contain personal data. Use IDs only.
 */
interface DomainEventLog
{
    /**
     * @param  array<string, scalar|null>  $context
     */
    public function record(string $name, array $context = []): void;
}
