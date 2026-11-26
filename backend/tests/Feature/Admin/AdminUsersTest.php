<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// docs/product/admin.md: the users list, disable and enable.

beforeEach(function (): void {
    withHeader('Referer', config()->string('app.url'));
});

function adminTestSessionRow(string $userId): void
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

it('lists the users, the oldest first', function (): void {
    $admin = User::factory()->admin()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'created_at' => '2026-11-20 10:00:00']);
    $bo = User::factory()->create(['name' => 'Bo', 'email' => 'bo@example.com', 'created_at' => '2026-11-24 10:00:00']);
    User::factory()->disabled()->create(['name' => 'Cy', 'email' => 'cy@example.com', 'created_at' => '2026-11-22 10:00:00']);
    actingAs($admin);

    getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonPath('0.name', 'Ada')
        ->assertJsonPath('0.role', 'admin')
        ->assertJsonPath('0.disabled', false)
        ->assertJsonPath('1.name', 'Cy')
        ->assertJsonPath('1.disabled', true)
        ->assertJsonPath('2.id', $bo->id)
        ->assertJsonPath('2.email', 'bo@example.com')
        ->assertJsonPath('2.joined_at', '2026-11-24T10:00:00+00:00');
});

it('refuses each users endpoint to a user who is not an admin', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    actingAs($user);

    getJson('/api/admin/users')->assertForbidden();
    postJson("/api/admin/users/{$other->id}/disable")->assertForbidden();
    postJson("/api/admin/users/{$other->id}/enable")->assertForbidden();

    expect($other->refresh()->disabled_at)->toBeNull();
});

it('SCN-AUTH-ADMIN-004 disables a user and ends the sessions', function (): void {
    $admin = User::factory()->admin()->create();
    $bo = User::factory()->create(['password' => 'a good long password']);
    adminTestSessionRow($bo->id);
    actingAs($admin);
    $scenario = scenario('SCN-AUTH-ADMIN-004', ['admin.user_disabled', 'auth.sessions_ended']);

    postJson("/api/admin/users/{$bo->id}/disable")
        ->assertOk()
        ->assertJsonPath('id', $bo->id)
        ->assertJsonPath('disabled', true);

    expect($bo->refresh()->disabled_at)->not->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $bo->id)->count())->toBe(0);
    $scenario->assertEvents();
});

it('records no sessions event when the user has no session', function (): void {
    $bo = User::factory()->create();
    actingAs(User::factory()->admin()->create());
    $events = Mockery::spy(DomainEventLog::class);
    app()->instance(DomainEventLog::class, $events);

    postJson("/api/admin/users/{$bo->id}/disable")->assertOk();

    $events->shouldHaveReceived('record')->with('admin.user_disabled', Mockery::any())->once();
    $events->shouldNotHaveReceived('record', ['auth.sessions_ended', Mockery::any()]);
});

it('stops the login of a disabled user', function (): void {
    $bo = User::factory()->create(['password' => 'a good long password']);
    actingAs(User::factory()->admin()->create());
    postJson("/api/admin/users/{$bo->id}/disable")->assertOk();
    app('auth')->forgetGuards();

    postJson('/api/login', ['email' => $bo->email, 'password' => 'a good long password'])
        ->assertUnprocessable();
});

it('SCN-AUTH-ADMIN-005 enables a disabled user', function (): void {
    $bo = User::factory()->disabled()->create();
    actingAs(User::factory()->admin()->create());
    $scenario = scenario('SCN-AUTH-ADMIN-005', ['admin.user_enabled']);

    postJson("/api/admin/users/{$bo->id}/enable")
        ->assertOk()
        ->assertJsonPath('disabled', false);

    expect($bo->refresh()->disabled_at)->toBeNull();
    $scenario->assertEvents();
});

it('SCN-AUTH-ADMIN-006 refuses to disable the own account', function (): void {
    $admin = User::factory()->admin()->create();
    actingAs($admin);
    $scenario = scenario('SCN-AUTH-ADMIN-006', ['admin.action_refused']);

    postJson("/api/admin/users/{$admin->id}/disable")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user' => 'You cannot disable your own account.']);

    expect($admin->refresh()->disabled_at)->toBeNull();
    $scenario->assertEvents();
});

it('can disable another admin', function (): void {
    $other = User::factory()->admin()->create();
    actingAs(User::factory()->admin()->create());

    postJson("/api/admin/users/{$other->id}/disable")->assertOk();

    expect($other->refresh()->disabled_at)->not->toBeNull();
});

it('returns 404 for a user that does not exist', function (): void {
    actingAs(User::factory()->admin()->create());

    postJson('/api/admin/users/01a21800-0000-7000-8000-000000000000/disable')->assertNotFound();
});
