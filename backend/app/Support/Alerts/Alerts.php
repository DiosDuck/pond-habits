<?php

declare(strict_types=1);

namespace App\Support\Alerts;

/**
 * A warning for the owner (ADR 0014): mail cap, failed-login spike. The server sends it to Sentry,
 * and Sentry emails the owner. The context holds counts and types only, never personal data.
 */
interface Alerts
{
    /**
     * @param  string  $key  The warning type. Sentry groups the warnings by it.
     * @param  array<string, scalar|null>  $context
     */
    public function warn(string $key, string $message, array $context = []): void;
}
