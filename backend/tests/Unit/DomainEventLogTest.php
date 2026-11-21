<?php

declare(strict_types=1);

use App\Support\DomainEvents\LogDomainEventLog;
use App\Support\DomainEvents\RecordingDomainEventLog;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

it('logs the event name as the message and in the context', function (): void {
    $handler = new TestHandler;
    $log = new LogDomainEventLog(new Logger('domain', [$handler]));

    $log->record('pomodoro.completed', ['user_id' => 'u-1']);

    $record = $handler->getRecords()[0] ?? null;
    expect($record?->message)->toBe('pomodoro.completed')
        ->and($record?->context)->toBe(['event' => 'pomodoro.completed', 'user_id' => 'u-1']);
});

it('keeps the recorded events in order', function (): void {
    $log = new RecordingDomainEventLog;

    $log->record('pomodoro.started', ['user_id' => 'u-1']);
    $log->record('pomodoro.completed', ['user_id' => 'u-1']);

    expect($log->names())->toBe(['pomodoro.started', 'pomodoro.completed'])
        ->and($log->events()[1])->toBe(['name' => 'pomodoro.completed', 'context' => ['user_id' => 'u-1']]);
});
