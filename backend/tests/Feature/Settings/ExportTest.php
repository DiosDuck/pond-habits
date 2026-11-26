<?php

declare(strict_types=1);

use App\Models\Invite;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withHeader;

// docs/product/settings.md: download the own data (GDPR Art. 15 and 20).

beforeEach(function (): void {
    withHeader('Referer', config()->string('app.url'));
    withHeader('Accept', 'application/json');
});

it('SCN-SET-004 gives a JSON file with the data of the user', function (): void {
    $user = User::factory()->create(['name' => 'Bo', 'email' => 'bo@example.com', 'timezone' => 'Europe/Bucharest', 'theme' => 'dark']);
    actingAs($user);
    $scenario = scenario('SCN-SET-004', ['user.data_exported']);

    $response = get('/api/settings/export')->assertOk();

    expect((string) $response->headers->get('Content-Disposition'))
        ->toStartWith('attachment; filename=pond-habits-export-')
        ->toEndWith('.json');
    $response->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.name', 'Bo')
        ->assertJsonPath('user.email', 'bo@example.com')
        ->assertJsonPath('user.role', 'user')
        ->assertJsonPath('user.timezone', 'Europe/Bucharest')
        ->assertJsonPath('user.theme', 'dark')
        ->assertJsonStructure(['exported_at', 'user' => ['created_at', 'email_verified_at']]);
    $scenario->assertEvents();
});

it('has no data of other persons', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'other@example.com']);
    Invite::issue('friend@example.com', $admin);
    actingAs($admin);

    $body = (string) json_encode(get('/api/settings/export')->assertOk()->json());

    expect($body)->not->toContain('other@example.com')
        ->and($body)->not->toContain('friend@example.com')
        ->and($body)->not->toContain('password');
});

it('returns 401 for a guest', function (): void {
    get('/api/settings/export')->assertUnauthorized();
});
