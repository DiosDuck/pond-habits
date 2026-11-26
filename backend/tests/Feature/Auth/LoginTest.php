<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// docs/product/login.md: Sanctum SPA cookie mode (ADR 0005).

beforeEach(function (): void {
    // Sanctum starts a session only for requests from the app's own origin (stateful domain).
    withHeader('Referer', config()->string('app.url'));
});

/**
 * @return array<string, string>
 */
function loginForm(User $user, string $password = 'a good long password'): array
{
    return ['email' => $user->email, 'password' => $password];
}

it('SCN-AUTH-003 logs in and starts a session', function (): void {
    $user = User::factory()->create(['password' => 'a good long password']);
    $scenario = scenario('SCN-AUTH-003', ['auth.logged_in']);

    postJson('/api/login', loginForm($user))
        ->assertOk()
        ->assertExactJson(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => 'user']);

    getJson('/api/me')->assertOk()->assertJsonPath('id', $user->id);
    $scenario->assertEvents();
});

it('returns the admin role for an admin', function (): void {
    $user = User::factory()->admin()->create(['password' => 'a good long password']);

    postJson('/api/login', loginForm($user))->assertOk()->assertJsonPath('role', 'admin');
});

it('returns 401 from /api/me without a login', function (): void {
    getJson('/api/me')->assertUnauthorized();
});

it('keeps a session for 30 days with no request', function (): void {
    expect(config()->integer('session.lifetime'))->toBe(30 * 24 * 60);
});

it('SCN-AUTH-004 rejects a wrong password with the general message', function (): void {
    $user = User::factory()->create(['password' => 'a good long password']);
    $scenario = scenario('SCN-AUTH-004', ['auth.login_failed']);

    postJson('/api/login', loginForm($user, 'a wrong password'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'The email or password is not correct.']);

    getJson('/api/me')->assertUnauthorized();
    $scenario->assertEvents();
});

it('rejects an unknown email with the same message', function (): void {
    postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'a good long password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'The email or password is not correct.']);
});

it('requires an email and a password', function (): void {
    postJson('/api/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('SCN-AUTH-005 stops the attempts after the limit', function (): void {
    config()->set('auth.login.max_attempts', 3);
    $user = User::factory()->create(['password' => 'a good long password']);
    $scenario = scenario('SCN-AUTH-005', ['auth.login_failed', 'auth.login_failed', 'auth.login_failed', 'auth.login_throttled']);

    foreach (range(1, 3) as $attempt) {
        postJson('/api/login', loginForm($user, 'a wrong password'))->assertUnprocessable();
    }
    postJson('/api/login', loginForm($user))->assertTooManyRequests();

    getJson('/api/me')->assertUnauthorized();
    $scenario->assertEvents();
});

it('SCN-AUTH-006 refuses a disabled user with the general message', function (): void {
    $user = User::factory()->disabled()->create(['password' => 'a good long password']);
    $scenario = scenario('SCN-AUTH-006', ['auth.login_refused']);

    postJson('/api/login', loginForm($user))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'The email or password is not correct.']);

    getJson('/api/me')->assertUnauthorized();
    $scenario->assertEvents();
});
