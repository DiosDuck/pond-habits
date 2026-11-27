<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\InviteNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SetPasswordNotification;
use App\Support\Alerts\Alerts;
use App\Support\Alerts\RecordingAlerts;
use App\Support\DomainEvents\DomainEventLog;
use App\Support\DomainEvents\RecordingDomainEventLog;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\travelTo;

// ADR 0014: a global daily mail cap, with a budget for each mail type, below the free plan of the
// mail provider. A mail over a budget is not sent. Each limit gives one warning (Sentry) each day:
// when 80 % of the daily total is used, and when a limit stops a mail. The day is the UTC day.

beforeEach(function (): void {
    config()->set('mail.cap', [
        'daily_total' => 5,
        'warn_at_percent' => 80,
        'per_type' => ['invite' => 2, 'set_password' => 3, 'password_reset' => 3],
    ]);
});

function capAlerts(): RecordingAlerts
{
    $alerts = new RecordingAlerts;
    app()->instance(Alerts::class, $alerts);

    return $alerts;
}

function capEvents(): RecordingDomainEventLog
{
    $log = new RecordingDomainEventLog;
    app()->instance(DomainEventLog::class, $log);

    return $log;
}

function sentMailCount(): int
{
    $transport = app('mailer')->getSymfonyTransport();
    assert($transport instanceof ArrayTransport);

    return $transport->messages()->count();
}

function sendInvite(): void
{
    Notification::route('mail', 'friend@example.com')->notify(new InviteNotification('token'));
}

function sendReset(User $user): void
{
    $user->notify(new ResetPasswordNotification('token'));
}

it('sends the mails of a type until its budget is used, then stops that type only', function (): void {
    $events = capEvents();
    capAlerts();
    $user = User::factory()->create();

    sendInvite();
    sendInvite();
    sendInvite();
    sendReset($user);

    expect(sentMailCount())->toBe(3)
        ->and($events->events())->toBe([['name' => 'mail.capped', 'context' => ['type' => 'invite']]]);
});

it('stops all mails when the daily total is used', function (): void {
    capAlerts();
    $user = User::factory()->create();

    sendInvite();
    sendInvite();
    sendReset($user);
    sendReset($user);
    $user->notify(new SetPasswordNotification('token'));
    $user->notify(new SetPasswordNotification('token'));

    expect(sentMailCount())->toBe(5);
});

it('warns once when 80 % of the daily total is used', function (): void {
    $alerts = capAlerts();
    $user = User::factory()->create();

    sendInvite();
    sendInvite();
    sendReset($user);
    expect($alerts->keys())->toBe([]);

    sendReset($user);
    sendReset($user);

    expect($alerts->keys())->toBe(['mail_cap_close']);
});

it('warns once each day when a limit stops a mail', function (): void {
    $alerts = capAlerts();

    sendInvite();
    sendInvite();
    sendInvite();
    sendInvite();

    expect($alerts->keys())->toBe(['mail_cap_reached'])
        ->and($alerts->contexts()[0])->toBe(['type' => 'invite']);
});

it('starts new budgets at 00:00 UTC', function (): void {
    capAlerts();
    travelTo(now()->utc()->setTime(23, 50));
    sendInvite();
    sendInvite();
    sendInvite();
    expect(sentMailCount())->toBe(2);

    travelTo(now()->utc()->addDay()->setTime(0, 5));
    sendInvite();

    expect(sentMailCount())->toBe(3);
});

it('counts a mail type that has no own budget in the daily total only', function (): void {
    capAlerts();
    config()->set('mail.cap.per_type', []);

    foreach (range(1, 6) as $ignored) {
        sendInvite();
    }

    expect(sentMailCount())->toBe(5);
});
