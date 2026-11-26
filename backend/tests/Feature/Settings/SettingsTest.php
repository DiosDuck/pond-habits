<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\withHeader;

// docs/product/settings.md: profile, time zone and theme.

beforeEach(function (): void {
    withHeader('Referer', config()->string('app.url'));
});

it('returns 401 for a guest', function (): void {
    getJson('/api/settings')->assertUnauthorized();
    patchJson('/api/settings', ['theme' => 'dark'])->assertUnauthorized();
});

it('returns the settings of the user, with the defaults', function (): void {
    $user = User::factory()->create(['name' => 'Bo', 'email' => 'bo@example.com']);
    actingAs($user);

    getJson('/api/settings')
        ->assertOk()
        ->assertExactJson(['name' => 'Bo', 'email' => 'bo@example.com', 'timezone' => 'UTC', 'theme' => 'system']);
});

it('SCN-SET-001 saves the name, the time zone and the theme', function (): void {
    $user = User::factory()->create();
    actingAs($user);
    $scenario = scenario('SCN-SET-001', ['user.settings_updated']);

    patchJson('/api/settings', ['name' => 'New Name', 'timezone' => 'Europe/Bucharest', 'theme' => 'dark'])
        ->assertOk()
        ->assertJsonPath('name', 'New Name')
        ->assertJsonPath('timezone', 'Europe/Bucharest')
        ->assertJsonPath('theme', 'dark');

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->timezone)->toBe('Europe/Bucharest')
        ->and($user->theme)->toBe('dark');
    $scenario->assertEvents();
});

it('changes only the fields in the request', function (): void {
    $user = User::factory()->create(['name' => 'Bo', 'timezone' => 'Europe/Bucharest']);
    actingAs($user);

    patchJson('/api/settings', ['theme' => 'light'])->assertOk();

    $user->refresh();
    expect($user->name)->toBe('Bo')
        ->and($user->timezone)->toBe('Europe/Bucharest')
        ->and($user->theme)->toBe('light');
});

it('rejects an empty name, an unknown time zone and an unknown theme', function (): void {
    actingAs(User::factory()->create());

    patchJson('/api/settings', ['name' => '', 'timezone' => 'Mars/Olympus', 'theme' => 'blue'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'timezone', 'theme']);
});

it('ignores the email and the role in the request', function (): void {
    $user = User::factory()->create(['email' => 'bo@example.com']);
    actingAs($user);

    patchJson('/api/settings', ['name' => 'Bo', 'email' => 'other@example.com', 'role' => 'admin'])->assertOk();

    $user->refresh();
    expect($user->email)->toBe('bo@example.com')
        ->and($user->role)->toBe(Role::User);
});

it('gives the time zone and the theme in /api/me', function (): void {
    actingAs(User::factory()->create(['timezone' => 'Europe/Bucharest', 'theme' => 'dark']));

    getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('timezone', 'Europe/Bucharest')
        ->assertJsonPath('theme', 'dark');
});
