<?php

declare(strict_types=1);

use App\Support\DomainEvents\DomainEventLog;
use PHPUnit\Framework\ExpectationFailedException;

// The ID is built from parts here, so that scripts/docs-check.sh does not read it as a real scenario.
$id = 'SCN-'.'HELPER-001';

it('passes when the app records the documented events in order', function () use ($id): void {
    $scenario = scenario($id, ['demo.started', 'demo.completed']);

    app(DomainEventLog::class)->record('demo.started');
    app(DomainEventLog::class)->record('demo.completed');

    $scenario->assertEvents();
    expect($scenario->id)->toBe($id);
});

it('fails when the events are different', function () use ($id): void {
    $scenario = scenario($id, ['demo.started', 'demo.completed']);

    app(DomainEventLog::class)->record('demo.completed');

    expect(static fn () => $scenario->assertEvents())
        ->toThrow(ExpectationFailedException::class, 'the recorded domain events are not the documented events');
});
