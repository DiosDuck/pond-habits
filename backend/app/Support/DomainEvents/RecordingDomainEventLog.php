<?php

declare(strict_types=1);

namespace App\Support\DomainEvents;

/**
 * Keeps the events in memory. Tests use it to check the event sequence of a scenario.
 * Do not use it in production: the list grows without limit.
 */
final class RecordingDomainEventLog implements DomainEventLog
{
    /** @var list<array{name: string, context: array<string, scalar|null>}> */
    private array $events = [];

    public function record(string $name, array $context = []): void
    {
        $this->events[] = ['name' => $name, 'context' => $context];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(static fn (array $event): string => $event['name'], $this->events);
    }

    /**
     * @return list<array{name: string, context: array<string, scalar|null>}>
     */
    public function events(): array
    {
        return $this->events;
    }
}
