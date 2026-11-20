<?php

declare(strict_types=1);

use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Support\Str;

it('writes each domain event as one JSON line to the domain log channel', function (): void {
    $path = storage_path('logs/domain-test-'.Str::random(8).'.log');
    config()->set('logging.channels.domain.path', $path);

    app(DomainEventLog::class)->record('pomodoro.completed', ['user_id' => 'u-1']);

    $line = json_decode(trim((string) file_get_contents($path)), true);
    unlink($path);

    expect($line)->toMatchArray([
        'message' => 'pomodoro.completed',
        'context' => ['event' => 'pomodoro.completed', 'user_id' => 'u-1'],
        'level_name' => 'INFO',
    ]);
});
