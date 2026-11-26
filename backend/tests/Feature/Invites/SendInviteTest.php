<?php

declare(strict_types=1);

use App\Models\Invite;
use App\Models\User;
use App\Notifications\InviteNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// docs/product/invites.md: only an admin sends invites (ADR 0005, ADR 0025).

beforeEach(function (): void {
    Notification::fake();
    withHeader('Referer', config()->string('app.url'));
});

it('SCN-AUTH-ADMIN-001 sends an invite with a registration link', function (): void {
    actingAs(User::factory()->admin()->create());
    $scenario = scenario('SCN-AUTH-ADMIN-001', ['invite.sent']);

    postJson('/api/admin/invites', ['email' => 'Friend@Example.com'])
        ->assertCreated()
        ->assertJsonPath('email', 'friend@example.com');

    $invite = Invite::query()->where('email', 'friend@example.com')->sole();
    expect($invite->expires_at->toDateString())->toBe(now()->addDays(7)->toDateString());
    Notification::assertSentOnDemand(
        InviteNotification::class,
        function (InviteNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            $url = (string) $notification->toMail($notifiable)->actionUrl;

            // The link has the token only. The email address is not in the URL.
            return $notifiable->routes['mail'] === 'friend@example.com'
                && str_starts_with($url, config()->string('app.url').'/register?token=')
                && ! str_contains($url, 'friend')
                && ! str_contains($url, 'example.com');
        },
    );
    $scenario->assertEvents();
});

it('SCN-AUTH-ADMIN-002 refuses a user who is not an admin', function (): void {
    actingAs(User::factory()->create());
    $scenario = scenario('SCN-AUTH-ADMIN-002', ['auth.admin_denied']);

    postJson('/api/admin/invites', ['email' => 'friend@example.com'])->assertForbidden();

    expect(Invite::count())->toBe(0);
    Notification::assertNothingSent();
    $scenario->assertEvents();
});

it('returns 401 for a guest', function (): void {
    postJson('/api/admin/invites', ['email' => 'friend@example.com'])->assertUnauthorized();
});

it('SCN-AUTH-ADMIN-003 refuses an address that has an account', function (): void {
    actingAs(User::factory()->admin()->create());
    User::factory()->create(['email' => 'friend@example.com']);
    $scenario = scenario('SCN-AUTH-ADMIN-003', ['invite.refused']);

    postJson('/api/admin/invites', ['email' => 'friend@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'This email address has an account already.']);

    expect(Invite::count())->toBe(0);
    Notification::assertNothingSent();
    $scenario->assertEvents();
});

it('rejects a text that is not an email address', function (): void {
    actingAs(User::factory()->admin()->create());

    postJson('/api/admin/invites', ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('replaces an older invite to the same address', function (): void {
    $admin = User::factory()->admin()->create();
    [, $oldToken] = Invite::issue('friend@example.com', $admin);
    actingAs($admin);

    postJson('/api/admin/invites', ['email' => 'friend@example.com'])->assertCreated();

    expect(Invite::count())->toBe(1)
        ->and(Invite::findByToken($oldToken))->toBeNull();
});

it('limits the invites for each admin', function (): void {
    config()->set('auth.invites.max_per_hour', 2);
    actingAs(User::factory()->admin()->create());

    postJson('/api/admin/invites', ['email' => 'one@example.com'])->assertCreated();
    postJson('/api/admin/invites', ['email' => 'two@example.com'])->assertCreated();
    postJson('/api/admin/invites', ['email' => 'three@example.com'])->assertTooManyRequests();

    Notification::assertSentOnDemandTimes(InviteNotification::class, 2);
});

it('deletes expired invites in the prune job', function (): void {
    $admin = User::factory()->admin()->create();
    Invite::issue('old@example.com', $admin);
    Carbon::setTestNow(now()->addDays(8));
    Invite::issue('new@example.com', $admin);

    expect(Artisan::call('model:prune', ['--model' => [Invite::class]]))->toBe(0)
        ->and(Invite::query()->pluck('email')->all())->toBe(['new@example.com']);
});

it('runs the prune job every day', function (): void {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toMatch('/0 3 \\* \\* \\*\\s+php artisan model:prune/');
});
