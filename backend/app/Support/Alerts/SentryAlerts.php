<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use Sentry\Severity;
use Sentry\State\Scope;

use function Sentry\captureMessage;
use function Sentry\withScope;

/**
 * Sends each warning as a Sentry event with the level "warning". The fingerprint groups the
 * warnings of one type in one Sentry issue, and Sentry emails the owner (ADR 0014).
 * Without a DSN (local work, tests), Sentry sends nothing.
 */
final readonly class SentryAlerts implements Alerts
{
    public function warn(string $key, string $message, array $context = []): void
    {
        withScope(static function (Scope $scope) use ($key, $message, $context): void {
            $scope->setFingerprint(['pond-alert', $key]);
            $scope->setExtras($context);
            captureMessage($message, Severity::warning());
        });
    }
}
