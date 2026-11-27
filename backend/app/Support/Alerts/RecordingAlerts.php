<?php

declare(strict_types=1);

namespace App\Support\Alerts;

/**
 * Keeps the warnings in memory. Tests use it to check which warnings the app sends.
 */
final class RecordingAlerts implements Alerts
{
    /** @var list<array{key: string, message: string, context: array<string, scalar|null>}> */
    private array $alerts = [];

    public function warn(string $key, string $message, array $context = []): void
    {
        $this->alerts[] = ['key' => $key, 'message' => $message, 'context' => $context];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(static fn (array $alert): string => $alert['key'], $this->alerts);
    }

    /**
     * @return list<array<string, scalar|null>>
     */
    public function contexts(): array
    {
        return array_map(static fn (array $alert): array => $alert['context'], $this->alerts);
    }
}
