<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\DomainEvents\RecordingDomainEventLog;
use PHPUnit\Framework\Assert;

/**
 * One documented scenario in a test (ADR 0008). Create it with scenario().
 * After the test acts, call assertEvents(): the recorded domain events must be the documented list.
 */
final readonly class Scenario
{
    /**
     * @param  list<string>  $expected
     */
    public function __construct(
        public string $id,
        private array $expected,
        private RecordingDomainEventLog $log,
    ) {}

    public function assertEvents(): void
    {
        Assert::assertSame(
            $this->expected,
            $this->log->names(),
            "Scenario {$this->id}: the recorded domain events are not the documented events.",
        );
    }
}
