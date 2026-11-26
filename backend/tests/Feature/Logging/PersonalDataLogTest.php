<?php

declare(strict_types=1);

use App\Logging\AddPersonalDataProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Each log channel removes personal data before it writes (privacy.md item 7, ADR 0015).

/**
 * Point a file channel to a new temporary file and return the path.
 */
function useTemporaryLogFile(string $channel): string
{
    $path = storage_path('logs/privacy-test-'.Str::random(8).'.log');
    config()->set("logging.channels.{$channel}.path", $path);
    Log::forgetChannel($channel);

    return $path;
}

function readAndDeleteLogFile(string $path): string
{
    $text = (string) file_get_contents($path);
    unlink($path);

    return $text;
}

it('adds the personal data processor to each log channel', function (): void {
    // "stack" uses the processors of its channels. "null" writes nothing. "emergency" is Laravel's
    // own fallback when a channel cannot start.
    $channels = collect(config()->array('logging.channels'))->except(['stack', 'null', 'emergency']);
    $missing = $channels
        ->filter(fn (mixed $config): bool => ! is_array($config) || ! in_array(AddPersonalDataProcessor::class, (array) ($config['tap'] ?? []), true))
        ->keys()
        ->all();

    expect($channels->count())->toBeGreaterThanOrEqual(5)
        ->and($missing)->toBe([]);
});

it('writes no email address to the log file', function (): void {
    $path = useTemporaryLogFile('single');

    Log::channel('single')->warning('Login failed for jane@example.com', ['email' => 'jane@example.com', 'user_id' => 'u-1']);
    Log::channel('single')->warning('Login failed for {email}', ['email' => 'jane@example.com']);

    $text = readAndDeleteLogFile($path);
    expect($text)->not->toContain('jane@example.com')
        ->and($text)->toContain('Login failed for [email]')
        ->and($text)->toContain('u-1');
});

it('writes no email address to the domain event log', function (): void {
    $path = useTemporaryLogFile('domain');

    Log::channel('domain')->info('auth.logged_in', ['event' => 'auth.logged_in', 'user_id' => 'u-1', 'email' => 'jane@example.com']);

    $line = json_decode(trim(readAndDeleteLogFile($path)), true);
    expect($line)->toMatchArray([
        'message' => 'auth.logged_in',
        'context' => ['event' => 'auth.logged_in', 'user_id' => 'u-1', 'email' => '[removed]'],
    ]);
});

it('writes no email address when the app reports an exception', function (): void {
    config()->set('logging.default', 'single');
    $path = useTemporaryLogFile('single');

    report(new RuntimeException('Duplicate key (email)=(jane@example.com)'));

    $text = readAndDeleteLogFile($path);
    expect($text)->not->toContain('jane@example.com')
        ->and($text)->toContain('RuntimeException')
        ->and($text)->toContain('Duplicate key (email)=([email])');
});
