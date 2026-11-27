<?php

declare(strict_types=1);

namespace App\Auth;

use App\Support\Alerts\Alerts;
use Illuminate\Support\Facades\Cache;

/**
 * Counts the failed logins of all visitors in fixed time windows (ADR 0014). When a window reaches
 * the threshold, the owner gets one warning for that window. It can show a password-guessing attack.
 */
final readonly class LoginFailureWatch
{
    public function __construct(private Alerts $alerts) {}

    public function failed(): void
    {
        $minutes = max(1, config()->integer('auth.alerts.failed_logins.window_minutes'));
        $threshold = config()->integer('auth.alerts.failed_logins.threshold');
        $window = intdiv(now()->getTimestamp(), $minutes * 60);
        $key = "login-failures:{$window}";

        Cache::add($key, 0, now()->addMinutes($minutes * 2));
        $failures = (int) Cache::increment($key);

        if ($failures === $threshold) {
            $this->alerts->warn('login_failures_spike', 'Failed logins passed the threshold.', [
                'failed_logins' => $failures,
                'window_minutes' => $minutes,
            ]);
        }
    }
}
