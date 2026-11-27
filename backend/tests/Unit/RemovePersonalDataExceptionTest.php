<?php

declare(strict_types=1);

use App\Logging\RemovePersonalData;
use Monolog\Level;
use Monolog\LogRecord;

// An exception in a log record becomes a plain array: class, clean message, code, "file:line"
// and a trace of "file:line" texts only. The call arguments in a trace can hold personal data,
// so the processor never writes them (privacy.md item 7, ADR 0015).

/**
 * @return array<array-key, mixed>
 */
function cleanException(Throwable $exception): array
{
    $record = new LogRecord(new DateTimeImmutable, 'test', Level::Error, 'failed', ['exception' => $exception]);

    return cleanArrayValue((new RemovePersonalData)($record)->context['exception']);
}

/**
 * @return array<array-key, mixed>
 */
function cleanArrayValue(mixed $value): array
{
    expect($value)->toBeArray();
    assert(is_array($value));

    return $value;
}

/**
 * Runs the callback and gives back the exception that it throws.
 */
function caughtException(Closure $callback): Throwable
{
    try {
        $callback();
    } catch (Throwable $exception) {
        return $exception;
    }

    throw new LogicException('The callback did not throw.');
}

it('writes the class, the clean message, the code and the place of an exception', function (): void {
    $line = __LINE__ + 1;
    $exception = new RuntimeException('Mail to jane@example.com failed from 203.0.113.7', 42);

    $clean = cleanException($exception);

    expect($clean['class'])->toBe(RuntimeException::class)
        ->and($clean['message'])->toBe('Mail to [email] failed from [ip]')
        ->and($clean['code'])->toBe(42)
        ->and($clean['file'])->toBe(__FILE__.':'.$line)
        ->and($clean)->not->toHaveKey('previous');
});

it('writes each trace frame as "file:line", and "[internal]:0" for a frame without a file', function (): void {
    $callLine = 0;
    $exception = caughtException(function () use (&$callLine): void {
        // The closure runs inside array_map(), a PHP function, so its own frame has no file.
        $callLine = __LINE__ + 1;
        array_map(static fn (): never => throw new LogicException('in a closure'), [1]);
    });

    $trace = cleanArrayValue(cleanException($exception)['trace']);

    expect($trace[0])->toBe('[internal]:0')
        ->and($trace[1])->toBe(__FILE__.':'.$callLine)
        ->and($trace)->toHaveCount(count($exception->getTrace()));
    foreach ($trace as $frame) {
        expect($frame)->toBeString()->toMatch('/^(\[internal\]|\/.+):\d+$/');
    }
});

it('never writes the call arguments of a trace frame', function (): void {
    $leak = static function (string $email): never {
        throw new RuntimeException('failed');
    };

    $exception = caughtException(static fn (): never => $leak('jane@example.com'));

    expect(json_encode(cleanException($exception)))->not->toContain('jane');
});

it('cleans the previous exception in the same way', function (): void {
    $innerLine = __LINE__ + 1;
    $inner = new InvalidArgumentException('Bad token for bo@example.com', 7);
    $outer = new RuntimeException('Outer', 1, $inner);

    $previous = cleanArrayValue(cleanException($outer)['previous']);

    expect($previous['class'])->toBe(InvalidArgumentException::class)
        ->and($previous['message'])->toBe('Bad token for [email]')
        ->and($previous['code'])->toBe(7)
        ->and($previous['file'])->toBe(__FILE__.':'.$innerLine);
});
