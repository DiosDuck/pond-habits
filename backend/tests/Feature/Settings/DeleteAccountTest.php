<?php

declare(strict_types=1);

use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withHeader;

// docs/product/settings.md: delete the own account (GDPR Art. 17, privacy.md item 2).

beforeEach(function (): void {
    withHeader('Referer', config()->string('app.url'));
});

function deleteAccountSessionRow(string $userId): void
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

it('SCN-SET-005 deletes the account and its data and ends the sessions', function (): void {
    $user = User::factory()->create(['password' => 'a good long password']);
    deleteAccountSessionRow($user->id);
    actingAs($user);
    $scenario = scenario('SCN-SET-005', ['user.deleted']);

    deleteJson('/api/settings/account', ['password' => 'a good long password'])->assertNoContent();

    expect(User::query()->whereKey($user->id)->first())->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    app('auth')->forgetGuards();
    getJson('/api/me')->assertUnauthorized();
    $scenario->assertEvents();
});

it('deletes the invites that the user sent', function (): void {
    $admin = User::factory()->admin()->create(['password' => 'a good long password']);
    User::factory()->admin()->create();
    Invite::issue('friend@example.com', $admin);
    actingAs($admin);

    deleteJson('/api/settings/account', ['password' => 'a good long password'])->assertNoContent();

    expect(Invite::count())->toBe(0);
});

it('SCN-SET-006 refuses a wrong password', function (): void {
    $user = User::factory()->create(['password' => 'a good long password']);
    actingAs($user);
    $scenario = scenario('SCN-SET-006', ['user.delete_refused']);

    deleteJson('/api/settings/account', ['password' => 'not the password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'The password is not correct.']);

    expect(User::query()->whereKey($user->id)->first())->not->toBeNull();
    $scenario->assertEvents();
});

it('SCN-SET-007 refuses the only active admin', function (): void {
    $admin = User::factory()->admin()->create(['password' => 'a good long password']);
    User::factory()->admin()->disabled()->create();
    actingAs($admin);
    $scenario = scenario('SCN-SET-007', ['user.delete_refused']);

    deleteJson('/api/settings/account', ['password' => 'a good long password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['account' => 'You are the only admin. Another admin must exist first.']);

    expect(User::query()->whereKey($admin->id)->first())->not->toBeNull();
    $scenario->assertEvents();
});

it('deletes an admin when another active admin exists', function (): void {
    $admin = User::factory()->admin()->create(['password' => 'a good long password']);
    User::factory()->admin()->create();
    actingAs($admin);

    deleteJson('/api/settings/account', ['password' => 'a good long password'])->assertNoContent();

    expect(User::query()->whereKey($admin->id)->first())->toBeNull();
});

it('returns 401 for a guest', function (): void {
    deleteJson('/api/settings/account', ['password' => 'x'])->assertUnauthorized();
});
