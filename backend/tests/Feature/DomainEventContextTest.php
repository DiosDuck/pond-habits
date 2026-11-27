<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use App\Support\DomainEvents\RecordingDomainEventLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// The scenario tests check the names of the domain events. This file checks the data of each event:
// the IDs that tell who did what (ADR 0008, ADR 0015). The data has IDs only, never an email or a name.

const EVENT_TEST_PASSWORD = 'a good long password';

beforeEach(function (): void {
    withHeader('Referer', config()->string('app.url'));
    Notification::fake();
});

function eventLog(): RecordingDomainEventLog
{
    $log = new RecordingDomainEventLog;
    app()->instance(DomainEventLog::class, $log);

    return $log;
}

/**
 * @param  array<string, scalar|null>  $context
 * @return array{name: string, context: array<string, scalar|null>}
 */
function recordedEvent(string $name, array $context = []): array
{
    return ['name' => $name, 'context' => $context];
}

/**
 * @param  array<string, mixed>  $attributes
 */
function eventUser(array $attributes = []): User
{
    return User::factory()->create(['password' => EVENT_TEST_PASSWORD, ...$attributes]);
}

function eventAdmin(): User
{
    return User::factory()->admin()->create(['password' => EVENT_TEST_PASSWORD]);
}

function eventSessionRow(User $user): void
{
    DB::table('sessions')->insert([
        'id' => bin2hex(random_bytes(20)),
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);
}

it('records the user ID when the settings change', function (): void {
    $log = eventLog();
    $user = eventUser();
    actingAs($user);

    patchJson('/api/settings', ['theme' => 'dark'])->assertOk();

    expect($log->events())->toBe([recordedEvent('user.settings_updated', ['user_id' => $user->id])]);
});

it('records the user ID for an export from the Settings page', function (): void {
    $log = eventLog();
    $user = eventUser();
    actingAs($user);

    getJson('/api/settings/export')->assertOk();

    expect($log->events())->toBe([recordedEvent('user.data_exported', ['user_id' => $user->id])]);
});

it('records the user ID and "operator" for an export by the operator command', function (): void {
    Storage::fake('local');
    $log = eventLog();
    $user = eventUser(['email' => 'bo@example.com']);

    expect(Artisan::call('app:export-user', ['email' => 'bo@example.com']))->toBe(0);

    expect($log->events())->toBe([recordedEvent('user.data_exported', ['user_id' => $user->id, 'by' => 'operator'])]);
});

it('records the user ID and "operator" for a deletion by the operator command', function (): void {
    $log = eventLog();
    $user = eventUser(['email' => 'bo@example.com']);
    $userId = $user->id;

    expect(Artisan::call('app:delete-user', ['email' => 'bo@example.com', '--force' => true]))->toBe(0);

    expect($log->events())->toBe([recordedEvent('user.deleted', ['user_id' => $userId, 'by' => 'operator'])]);
});

it('records the user ID when a user deletes the own account', function (): void {
    $log = eventLog();
    $user = eventUser();
    $userId = $user->id;
    actingAs($user);

    deleteJson('/api/settings/account', ['password' => EVENT_TEST_PASSWORD])->assertNoContent();

    expect($log->events())->toBe([recordedEvent('user.deleted', ['user_id' => $userId])]);
});

it('records the user ID when a deletion is refused for a wrong password', function (): void {
    $log = eventLog();
    $user = eventUser();
    actingAs($user);

    deleteJson('/api/settings/account', ['password' => 'not the password'])->assertUnprocessable();

    expect($log->events())->toBe([recordedEvent('user.delete_refused', ['user_id' => $user->id])]);
});

it('records the user ID when the only admin cannot delete the own account', function (): void {
    $log = eventLog();
    $admin = eventAdmin();
    actingAs($admin);

    deleteJson('/api/settings/account', ['password' => EVENT_TEST_PASSWORD])->assertUnprocessable();

    expect($log->events())->toBe([recordedEvent('user.delete_refused', ['user_id' => $admin->id])]);
});

it('records the user ID when a password change is refused', function (): void {
    $log = eventLog();
    $user = eventUser();
    actingAs($user);

    postJson('/api/settings/password', [
        'current_password' => 'not the password',
        'password' => 'a brand new long password',
        'password_confirmation' => 'a brand new long password',
    ])->assertUnprocessable();

    expect($log->events())->toBe([recordedEvent('auth.password_change_refused', ['user_id' => $user->id])]);
});

it('records the user ID when the password changes and the sessions end', function (): void {
    $log = eventLog();
    $user = eventUser();
    eventSessionRow($user);
    actingAs($user);

    postJson('/api/settings/password', [
        'current_password' => EVENT_TEST_PASSWORD,
        'password' => 'a brand new long password',
        'password_confirmation' => 'a brand new long password',
    ])->assertNoContent();

    expect($log->events())->toBe([
        recordedEvent('auth.password_changed', ['user_id' => $user->id]),
        recordedEvent('auth.sessions_ended', ['user_id' => $user->id]),
    ]);
});

it('records the invite ID and the admin ID when an admin sends an invite', function (): void {
    $log = eventLog();
    $admin = eventAdmin();
    actingAs($admin);

    $inviteId = postJson('/api/admin/invites', ['email' => 'friend@example.com'])->assertCreated()->json('id');
    assert(is_string($inviteId));

    expect($log->events())->toBe([recordedEvent('invite.sent', ['invite_id' => $inviteId, 'user_id' => $admin->id])]);
});

it('records the admin ID when an invite is refused for a known email', function (): void {
    $log = eventLog();
    $admin = eventAdmin();
    eventUser(['email' => 'bo@example.com']);
    actingAs($admin);

    postJson('/api/admin/invites', ['email' => 'bo@example.com'])->assertUnprocessable();

    expect($log->events())->toBe([recordedEvent('invite.refused', ['user_id' => $admin->id])]);
});

it('records the invite ID and the admin ID when an admin cancels an invite', function (): void {
    $admin = eventAdmin();
    actingAs($admin);
    $inviteId = postJson('/api/admin/invites', ['email' => 'friend@example.com'])->assertCreated()->json('id');
    assert(is_string($inviteId));
    $log = eventLog();

    deleteJson("/api/admin/invites/{$inviteId}")->assertNoContent();

    expect($log->events())->toBe([recordedEvent('admin.invite_cancelled', ['invite_id' => $inviteId, 'admin_id' => $admin->id])]);
});

it('records the user ID and the admin ID when an admin disables a user and the sessions end', function (): void {
    $log = eventLog();
    $admin = eventAdmin();
    $user = eventUser();
    eventSessionRow($user);
    actingAs($admin);

    postJson("/api/admin/users/{$user->id}/disable")->assertOk();

    expect($log->events())->toBe([
        recordedEvent('admin.user_disabled', ['user_id' => $user->id, 'admin_id' => $admin->id]),
        recordedEvent('auth.sessions_ended', ['user_id' => $user->id]),
    ]);
});

it('records the admin ID when an admin tries to disable the own account', function (): void {
    $log = eventLog();
    $admin = eventAdmin();
    actingAs($admin);

    postJson("/api/admin/users/{$admin->id}/disable")->assertUnprocessable();

    expect($log->events())->toBe([recordedEvent('admin.action_refused', ['user_id' => $admin->id])]);
});

it('records the user ID and the admin ID when an admin enables a user', function (): void {
    $log = eventLog();
    $admin = eventAdmin();
    $user = User::factory()->disabled()->create();
    actingAs($admin);

    postJson("/api/admin/users/{$user->id}/enable")->assertOk();

    expect($log->events())->toBe([recordedEvent('admin.user_enabled', ['user_id' => $user->id, 'admin_id' => $admin->id])]);
});

it('records the user ID when a user who is not an admin opens an admin endpoint', function (): void {
    $log = eventLog();
    $user = eventUser();
    actingAs($user);

    getJson('/api/admin/users')->assertForbidden();

    expect($log->events())->toBe([recordedEvent('auth.admin_denied', ['user_id' => $user->id])]);
});

it('records the user ID at login and at logout', function (): void {
    $log = eventLog();
    $user = eventUser();

    postJson('/api/login', ['email' => $user->email, 'password' => EVENT_TEST_PASSWORD])->assertOk();
    postJson('/api/logout')->assertNoContent();

    expect($log->events())->toBe([
        recordedEvent('auth.logged_in', ['user_id' => $user->id]),
        recordedEvent('auth.logged_out', ['user_id' => $user->id]),
    ]);
});

it('records the user ID for a wrong password, and no ID for an unknown email', function (): void {
    $log = eventLog();
    $user = eventUser();

    postJson('/api/login', ['email' => $user->email, 'password' => 'a wrong password'])->assertUnprocessable();
    postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'a wrong password'])->assertUnprocessable();

    expect($log->events())->toBe([
        recordedEvent('auth.login_failed', ['user_id' => $user->id]),
        recordedEvent('auth.login_failed', ['user_id' => null]),
    ]);
});

it('records the user ID when a disabled user tries to log in', function (): void {
    $log = eventLog();
    $user = User::factory()->disabled()->create(['password' => EVENT_TEST_PASSWORD]);

    postJson('/api/login', ['email' => $user->email, 'password' => EVENT_TEST_PASSWORD])->assertUnprocessable();

    expect($log->events())->toBe([recordedEvent('auth.login_refused', ['user_id' => $user->id])]);
});

it('records the user ID for a reset request of an active user, and no ID for a disabled user', function (): void {
    $log = eventLog();
    $user = eventUser(['email' => 'bo@example.com']);
    User::factory()->disabled()->create(['email' => 'cy@example.com']);

    postJson('/api/password/forgot', ['email' => 'bo@example.com'])->assertNoContent();
    postJson('/api/password/forgot', ['email' => 'cy@example.com'])->assertNoContent();

    expect($log->events())->toBe([
        recordedEvent('auth.reset_requested', ['user_id' => $user->id]),
        recordedEvent('auth.password_link_sent', ['user_id' => $user->id]),
        recordedEvent('auth.reset_requested', ['user_id' => null]),
    ]);
});

it('records the new admin ID when the operator creates the first admin', function (): void {
    $log = eventLog();

    expect(Artisan::call('app:create-admin', ['email' => 'ada@example.com', '--name' => 'Ada']))->toBe(0);

    $adminId = User::query()->where('email', 'ada@example.com')->value('id');
    assert(is_string($adminId));
    expect($log->events())->toBe([
        recordedEvent('auth.admin_created', ['user_id' => $adminId]),
        recordedEvent('auth.password_link_sent', ['user_id' => $adminId]),
    ]);
});
