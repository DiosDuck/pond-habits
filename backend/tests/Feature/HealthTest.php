<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;

use function Pest\Laravel\getJson;

it('reports ok when the database answers', function (): void {
    getJson('/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'checks' => ['database' => 'ok']]);
});

it('reports fail with status 503 when the database does not answer', function (): void {
    $db = Mockery::mock(ConnectionInterface::class);
    $db->shouldReceive('select')->andThrow(new RuntimeException('The database is down.'));
    app()->instance(ConnectionInterface::class, $db);

    getJson('/health')
        ->assertStatus(503)
        ->assertExactJson(['status' => 'fail', 'checks' => ['database' => 'fail']]);
});

it('does not start a session', function (): void {
    getJson('/health')->assertCookieMissing(config()->string('session.cookie'));
});
