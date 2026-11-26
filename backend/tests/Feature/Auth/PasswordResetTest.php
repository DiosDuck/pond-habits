<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\postJson;

// docs/product/password-reset.md (ADR 0005).

beforeEach(function (): void {
    Notification::fake();
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response("0000000000000000000000000000000000A:1\r\n")]);
});

/**
 * A server session row, as the "database" session driver writes it.
 */
function sessionRow(?string $userId): void
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

it('SCN-AUTH-011 mails a reset link to an active account', function (): void {
    $user = User::factory()->create(['email' => 'friend@example.com']);
    $scenario = scenario('SCN-AUTH-011', ['auth.reset_requested', 'auth.password_link_sent']);

    postJson('/api/password/forgot', ['email' => 'Friend@Example.com'])->assertNoContent();

    Notification::assertSentTo(
        $user,
        ResetPasswordNotification::class,
        function (ResetPasswordNotification $notification) use ($user): bool {
            $url = (string) $notification->toMail($user)->actionUrl;

            // The reset link opens the set-password page. The address is not in the URL.
            return str_starts_with($url, config()->string('app.url').'/set-password?token=')
                && ! str_contains($url, 'friend')
                && ! str_contains($url, 'example.com');
        },
    );
    $scenario->assertEvents();
});

it('gives a token that the set-password endpoint accepts', function (): void {
    $user = User::factory()->create(['email' => 'friend@example.com']);
    postJson('/api/password/forgot', ['email' => 'friend@example.com'])->assertNoContent();

    $token = '';
    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user, &$token): bool {
        parse_str((string) parse_url((string) $notification->toMail($user)->actionUrl, PHP_URL_QUERY), $query);
        $token = is_string($query['token'] ?? null) ? $query['token'] : '';

        return $token !== '';
    });

    $password = 'a brand new long password';
    postJson('/api/password/set', ['token' => $token, 'email' => 'friend@example.com', 'password' => $password, 'password_confirmation' => $password])
        ->assertNoContent();
    expect(Hash::check($password, $user->refresh()->password))->toBeTrue();
});

it('SCN-AUTH-012 gives the same answer and no mail for an unknown address', function (): void {
    $scenario = scenario('SCN-AUTH-012', ['auth.reset_requested']);

    postJson('/api/password/forgot', ['email' => 'nobody@example.com'])->assertNoContent();

    Notification::assertNothingSent();
    $scenario->assertEvents();
});

it('gives the same answer and no mail for a disabled account', function (): void {
    User::factory()->disabled()->create(['email' => 'friend@example.com']);

    postJson('/api/password/forgot', ['email' => 'friend@example.com'])->assertNoContent();

    Notification::assertNothingSent();
});

it('SCN-AUTH-013 gives the same answer and no mail over the limit for one address', function (): void {
    config()->set('auth.reset.max_per_address_per_hour', 1);
    User::factory()->create(['email' => 'friend@example.com']);
    postJson('/api/password/forgot', ['email' => 'friend@example.com'])->assertNoContent();
    $scenario = scenario('SCN-AUTH-013', ['auth.reset_requested', 'auth.reset_limited']);

    postJson('/api/password/forgot', ['email' => 'friend@example.com'])->assertNoContent();

    Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    $scenario->assertEvents();
});

it('returns 429 over the limit for one IP address', function (): void {
    config()->set('auth.reset.max_per_ip_per_hour', 2);

    postJson('/api/password/forgot', ['email' => 'one@example.com'])->assertNoContent();
    postJson('/api/password/forgot', ['email' => 'two@example.com'])->assertNoContent();
    postJson('/api/password/forgot', ['email' => 'three@example.com'])->assertTooManyRequests();
});

it('requires an email address', function (): void {
    postJson('/api/password/forgot', ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('SCN-AUTH-014 ends the other sessions of the user after a new password', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    sessionRow($user->id);
    sessionRow($user->id);
    sessionRow($other->id);
    $token = Password::createToken($user);
    $scenario = scenario('SCN-AUTH-014', ['auth.password_set', 'auth.sessions_ended']);

    $password = 'a brand new long password';
    postJson('/api/password/set', ['token' => $token, 'email' => $user->email, 'password' => $password, 'password_confirmation' => $password])
        ->assertNoContent();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $other->id)->count())->toBe(1);
    $scenario->assertEvents();
});
