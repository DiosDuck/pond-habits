<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// docs/product/settings.md: change the password. All sessions end, this device too.

beforeEach(function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response("0000000000000000000000000000000000A:1\r\n")]);
    withHeader('Referer', config()->string('app.url'));
});

function passwordChangeSessionRow(string $userId): void
{
    DB::table('sessions')->insert([
        'id' => bin2hex(random_bytes(20)),
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);
}

/**
 * @return array<string, string>
 */
function passwordChangeForm(string $current, string $new = 'a brand new long password'): array
{
    return ['current_password' => $current, 'password' => $new, 'password_confirmation' => $new];
}

it('SCN-SET-002 changes the password and ends all sessions', function (): void {
    $user = User::factory()->create(['password' => 'a good long password']);
    passwordChangeSessionRow($user->id);
    actingAs($user);
    $scenario = scenario('SCN-SET-002', ['auth.password_changed', 'auth.sessions_ended']);

    postJson('/api/settings/password', passwordChangeForm('a good long password'))->assertNoContent();

    expect(Hash::check('a brand new long password', $user->refresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    app('auth')->forgetGuards();
    getJson('/api/me')->assertUnauthorized();
    $scenario->assertEvents();
});

it('SCN-SET-003 refuses a wrong current password', function (): void {
    $user = User::factory()->create(['password' => 'a good long password']);
    actingAs($user);
    $scenario = scenario('SCN-SET-003', ['auth.password_change_refused']);

    postJson('/api/settings/password', passwordChangeForm('not the password'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password' => 'The current password is not correct.']);

    expect(Hash::check('a good long password', $user->refresh()->password))->toBeTrue();
    $scenario->assertEvents();
});

it('uses the same rules for the new password', function (): void {
    actingAs(User::factory()->create(['password' => 'a good long password']));

    postJson('/api/settings/password', passwordChangeForm('a good long password', 'short pass1'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('returns 401 for a guest', function (): void {
    postJson('/api/settings/password', passwordChangeForm('x'))->assertUnauthorized();
});
