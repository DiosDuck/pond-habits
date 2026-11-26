<?php

declare(strict_types=1);

use App\Logging\RemovePersonalData;
use Monolog\Level;
use Monolog\LogRecord;

// Logs contain the user ID only. Logs never contain emails or other personal data
// (privacy.md item 7, ADR 0015). The processor cleans each log record before it is written.

/**
 * @param  array<mixed>  $context
 * @param  array<mixed>  $extra
 */
function cleanRecord(string $message, array $context = [], array $extra = []): LogRecord
{
    $record = new LogRecord(new DateTimeImmutable, 'test', Level::Info, $message, $context, $extra);

    return (new RemovePersonalData)($record);
}

it('replaces an email address in the message', function (): void {
    expect(cleanRecord('Login failed for Jane.Doe+x@Mail.Demo.uk now')->message)
        ->toBe('Login failed for [email] now');
});

it('replaces email addresses in the context and in the extra data, also when nested', function (): void {
    $record = cleanRecord('event', ['note' => 'from jane@example.com', 'list' => ['a@example.net', 'ok']], ['x' => ['y' => 'c@team.demo']]);

    expect($record->context)->toBe(['note' => 'from [email]', 'list' => ['[email]', 'ok']])
        ->and($record->extra)->toBe(['x' => ['y' => '[email]']]);
});

it('removes the value of a personal field, also when nested', function (): void {
    $record = cleanRecord('event', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'current_password' => 'old-password',
        'token' => 'abc123',
        'remember_token' => 'def456',
        'ip' => '203.0.113.7',
        'ip_address' => '2001:db8::1',
        'user_agent' => 'Mozilla/5.0',
        'user' => ['Email' => 'jane@example.com', 'id' => 'u-1'],
    ]);

    expect($record->context)->toBe([
        'email' => '[removed]',
        'name' => '[removed]',
        'password' => '[removed]',
        'password_confirmation' => '[removed]',
        'current_password' => '[removed]',
        'token' => '[removed]',
        'remember_token' => '[removed]',
        'ip' => '[removed]',
        'ip_address' => '[removed]',
        'user_agent' => '[removed]',
        'user' => ['Email' => '[removed]', 'id' => 'u-1'],
    ]);
});

it('keeps the event name, the IDs and other values', function (): void {
    $context = [
        'event' => 'auth.logged_in',
        'user_id' => '019a0000-0000-7000-8000-000000000001',
        'invite_id' => 'i-1',
        'admin_id' => 'a-1',
        'userId' => 'u-1',
        'by' => 'operator',
        'count' => 3,
        'ok' => true,
        'none' => null,
    ];
    $record = cleanRecord('auth.logged_in', $context);

    expect($record->message)->toBe('auth.logged_in')
        ->and($record->context)->toBe($context);
});

it('removes the token from a link', function (): void {
    expect(cleanRecord('GET /register?token=abc123&step=2 and /set-password?token=x-Y_9')->message)
        ->toBe('GET /register?token=[removed]&step=2 and /set-password?token=[removed]');
});

it('replaces an IPv4 address in a text', function (): void {
    expect(cleanRecord('Too many logins from 203.0.113.7.')->message)
        ->toBe('Too many logins from [ip].');
});

it('cleans the message of an exception and of the previous exception', function (): void {
    $exception = new RuntimeException(
        'Duplicate key (email)=(jane@example.com)',
        0,
        new LogicException('Request from 203.0.113.7'),
    );
    $cleaned = cleanRecord('error', ['exception' => $exception])->context['exception'];
    $previous = is_array($cleaned) ? ($cleaned['previous'] ?? null) : null;

    expect($cleaned)->toBeArray()
        ->toMatchArray([
            'class' => RuntimeException::class,
            'message' => 'Duplicate key (email)=([email])',
        ])
        ->and($previous)->toBeArray()
        ->toMatchArray([
            'class' => LogicException::class,
            'message' => 'Request from [ip]',
        ]);
});

it('writes other objects as their class name only', function (): void {
    $record = cleanRecord('event', ['data' => (object) ['email' => 'jane@example.com']]);

    expect($record->context)->toBe(['data' => '[object stdClass]']);
});
