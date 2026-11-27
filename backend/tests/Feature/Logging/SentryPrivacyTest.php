<?php

declare(strict_types=1);

use App\Logging\SentryEventScrubber;
use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Sentry\UserDataBag;

// Error reports go to Sentry (ADR 0014). They contain the user ID, never an email, a name, a
// password, a token, an IP address, cookies or request bodies (privacy.md items 7 and 12).

it('sends no personal data by default, no SQL values, no log breadcrumbs and no traces', function (): void {
    expect(config('sentry.send_default_pii'))->toBeFalse()
        ->and(config('sentry.breadcrumbs.sql_bindings'))->toBeFalse()
        ->and(config('sentry.breadcrumbs.logs'))->toBeFalse()
        ->and(config('sentry.traces_sample_rate'))->toBeIn([null, 0.0])
        // An array in the config (not a closure), so that "php artisan config:cache" works.
        ->and(config()->array('sentry.before_send'))->toHaveCount(2)
        ->and(config('sentry.before_send.0'))->toBe(SentryEventScrubber::class)
        ->and(config('sentry.before_send.1'))->toBe('scrub');
});

it('is off when no DSN is set (local work and tests)', function (): void {
    expect(config('sentry.dsn'))->toBeNull();
});

it('removes emails, IP addresses and link tokens from the texts of an event', function (): void {
    $event = Event::createEvent();
    $event->setMessage('Mail to jane@example.com from 203.0.113.7 failed');
    $event->setExceptions([new ExceptionDataBag(new RuntimeException('No user bo@example.com, see /reset?token=abc123'))]);
    $event->setBreadcrumb([new Breadcrumb('info', 'default', 'mail', 'Sent to cy@example.com', ['to' => 'cy@example.com', 'user_id' => 'u-1'])]);
    $event->setExtra(['email' => 'jane@example.com', 'note' => 'from 203.0.113.7', 'user_id' => 'u-1']);
    $event->setContext('job', ['password' => 'secret-password', 'invite_id' => 'i-1']);

    $clean = SentryEventScrubber::scrub($event);
    $text = (string) json_encode([
        $clean->getMessage(),
        array_map(static fn (ExceptionDataBag $e): string => $e->getValue(), $clean->getExceptions()),
        array_map(static fn (Breadcrumb $b): array => [$b->getMessage(), $b->getMetadata()], $clean->getBreadcrumbs()),
        $clean->getExtra(),
        $clean->getContexts()['job'] ?? null,
    ]);

    foreach (['example.com', '203.0.113.7', 'abc123', 'secret-password'] as $personal) {
        expect($text)->not->toContain($personal);
    }
    foreach (['[email]', 'u-1', 'i-1'] as $kept) {
        expect($text)->toContain($kept);
    }
});

it('keeps only the method and the path of the request', function (): void {
    $event = Event::createEvent();
    $event->setRequest([
        'method' => 'POST',
        'url' => 'https://pondhabits.com/api/password/forgot?token=abc123',
        'query_string' => 'token=abc123',
        'cookies' => ['laravel-session' => 'secret-cookie'],
        'headers' => ['Cookie' => 'laravel-session=secret-cookie', 'Authorization' => 'Basic c2VjcmV0'],
        'data' => ['email' => 'jane@example.com'],
        'env' => ['REMOTE_ADDR' => '203.0.113.7'],
    ]);

    expect(SentryEventScrubber::scrub($event)->getRequest())
        ->toBe(['method' => 'POST', 'url' => 'https://pondhabits.com/api/password/forgot']);
});

it('keeps only the user ID of the user', function (): void {
    $event = Event::createEvent();
    $event->setUser(UserDataBag::createFromArray([
        'id' => 'u-1', 'email' => 'jane@example.com', 'username' => 'Jane', 'ip_address' => '203.0.113.7',
    ]));

    $user = SentryEventScrubber::scrub($event)->getUser();

    expect($user?->getId())->toBe('u-1')
        ->and($user?->getEmail())->toBeNull()
        ->and($user?->getUsername())->toBeNull()
        ->and($user?->getIpAddress())->toBeNull();
});

it('sets only the user ID on the error report scope after a login', function (): void {
    $user = User::factory()->create();

    event(new Authenticated('web', $user));

    $scopeUser = null;
    SentrySdk::getCurrentHub()->configureScope(static function (Scope $scope) use (&$scopeUser): void {
        $scopeUser = $scope->getUser();
    });
    expect($scopeUser?->getId())->toBe($user->id)
        ->and($scopeUser?->getEmail())->toBeNull()
        ->and($scopeUser?->getUsername())->toBeNull();
});
