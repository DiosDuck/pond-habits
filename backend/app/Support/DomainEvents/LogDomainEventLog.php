<?php

declare(strict_types=1);

namespace App\Support\DomainEvents;

use Psr\Log\LoggerInterface;

/**
 * Writes each domain event as one JSON line to the "domain" log channel.
 */
final readonly class LogDomainEventLog implements DomainEventLog
{
    public function __construct(private LoggerInterface $logger) {}

    public function record(string $name, array $context = []): void
    {
        $this->logger->info($name, ['event' => $name, ...$context]);
    }
}
