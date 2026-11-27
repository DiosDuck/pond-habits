<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Notifications\InviteNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SetPasswordNotification;
use App\Support\Alerts\Alerts;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Cache;

/**
 * The daily mail cap (ADR 0014): a global daily total and a budget for each mail type, below the
 * free plan of the mail provider. Laravel calls it before each mail; "false" stops the mail.
 * The counters are for the UTC day. Each limit gives one warning each day.
 * Laravel finds this listener by the type of handle() (event discovery): do not register it again.
 */
final readonly class MailCap
{
    private const array TYPES = [
        InviteNotification::class => 'invite',
        SetPasswordNotification::class => 'set_password',
        ResetPasswordNotification::class => 'password_reset',
    ];

    public function __construct(private Alerts $alerts, private DomainEventLog $events) {}

    public function handle(MessageSending $event): ?bool
    {
        $notification = $event->data['__laravel_notification'] ?? null;
        $type = is_string($notification) ? (self::TYPES[$notification] ?? 'other') : 'other';
        $day = now()->utc()->format('Y-m-d');
        $cap = config()->integer('mail.cap.daily_total');
        $budget = config('mail.cap.per_type.'.$type);

        $total = $this->count("mail-cap:{$day}:total");
        $used = $this->count("mail-cap:{$day}:{$type}");
        if ($total >= $cap || (is_int($budget) && $used >= $budget)) {
            $this->events->record('mail.capped', ['type' => $type]);
            $this->warnOnce("mail_cap_reached:{$day}:{$type}", 'mail_cap_reached', 'A daily mail limit stopped a mail.', ['type' => $type]);

            return false;
        }

        Cache::increment("mail-cap:{$day}:{$type}");
        $total = (int) Cache::increment("mail-cap:{$day}:total");
        if ($total === (int) ceil($cap * config()->integer('mail.cap.warn_at_percent') / 100)) {
            $this->warnOnce("mail_cap_close:{$day}", 'mail_cap_close', 'The daily mail cap is nearly used.', ['used' => $total, 'cap' => $cap]);
        }

        return null;
    }

    private function count(string $key): int
    {
        Cache::add($key, 0, now()->addDays(2));

        $value = Cache::get($key);

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param  array<string, scalar|null>  $context
     */
    private function warnOnce(string $onceKey, string $key, string $message, array $context): void
    {
        if (Cache::add('alert-sent:'.$onceKey, true, now()->addDays(2))) {
            $this->alerts->warn($key, $message, $context);
        }
    }
}
