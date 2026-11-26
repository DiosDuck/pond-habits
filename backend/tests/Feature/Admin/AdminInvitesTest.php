<?php

declare(strict_types=1);

use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// docs/product/admin.md: pending invites, cancel.

beforeEach(function (): void {
    withHeader('Referer', config()->string('app.url'));
});

it('lists the pending invites, the first to expire first', function (): void {
    $admin = User::factory()->admin()->create();
    Invite::issue('old@example.com', $admin);
    Carbon::setTestNow(now()->addDays(2));
    Invite::issue('newer@example.com', $admin);
    Carbon::setTestNow(now()->addDays(6));
    actingAs($admin);

    // "old" expired 1 day ago. Only "newer" is pending.
    getJson('/api/admin/invites')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.email', 'newer@example.com');
});

it('orders the pending invites by expiry date', function (): void {
    $admin = User::factory()->admin()->create();
    Invite::issue('first@example.com', $admin);
    Carbon::setTestNow(now()->addHour());
    Invite::issue('second@example.com', $admin);
    actingAs($admin);

    getJson('/api/admin/invites')
        ->assertOk()
        ->assertJsonPath('0.email', 'first@example.com')
        ->assertJsonPath('1.email', 'second@example.com');
});

it('SCN-AUTH-ADMIN-007 cancels a pending invite', function (): void {
    $admin = User::factory()->admin()->create();
    [$invite, $token] = Invite::issue('friend@example.com', $admin);
    actingAs($admin);
    $scenario = scenario('SCN-AUTH-ADMIN-007', ['admin.invite_cancelled']);

    deleteJson("/api/admin/invites/{$invite->id}")->assertNoContent();

    expect(Invite::findByToken($token))->toBeNull();
    $scenario->assertEvents();
});

it('stops the link of a cancelled invite', function (): void {
    $admin = User::factory()->admin()->create();
    [$invite, $token] = Invite::issue('friend@example.com', $admin);
    actingAs($admin);
    deleteJson("/api/admin/invites/{$invite->id}")->assertNoContent();

    postJson('/api/invites/check', ['token' => $token])->assertUnprocessable();
});

it('refuses the invite endpoints to a user who is not an admin', function (): void {
    [$invite] = Invite::issue('friend@example.com', User::factory()->admin()->create());
    actingAs(User::factory()->create());

    getJson('/api/admin/invites')->assertForbidden();
    deleteJson("/api/admin/invites/{$invite->id}")->assertForbidden();

    expect(Invite::count())->toBe(1);
});

it('returns 404 for an invite that does not exist', function (): void {
    actingAs(User::factory()->admin()->create());

    deleteJson('/api/admin/invites/01a21800-0000-7000-8000-000000000000')->assertNotFound();
});
