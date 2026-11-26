<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// docs/product/login.md: log out ends the server session.

beforeEach(function (): void {
    withHeader('Referer', config()->string('app.url'));
});

it('SCN-AUTH-007 logs out and ends the session', function (): void {
    actingAs(User::factory()->create());
    $scenario = scenario('SCN-AUTH-007', ['auth.logged_out']);

    postJson('/api/logout')->assertNoContent();

    // Forget the user that the test kept in memory, as a new request does.
    app('auth')->forgetGuards();
    getJson('/api/me')->assertUnauthorized();
    $scenario->assertEvents();
});

it('returns 401 for a logout without a login', function (): void {
    postJson('/api/logout')->assertUnauthorized();
});
