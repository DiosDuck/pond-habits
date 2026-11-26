<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\postJson;

// docs/product/login.md: set the password from the mailed link.

beforeEach(function (): void {
    // The breached-password check calls Have I Been Pwned. The tests never call the real API.
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response("0000000000000000000000000000000000A:1\r\n")]);
});

/**
 * @return array<string, string>
 */
function setPasswordForm(string $token, string $email, string $password = 'correct horse battery staple'): array
{
    return ['token' => $token, 'email' => $email, 'password' => $password, 'password_confirmation' => $password];
}

it('SCN-AUTH-002 sets the password from the link', function (): void {
    $user = User::factory()->admin()->unverified()->create();
    $token = Password::createToken($user);
    $scenario = scenario('SCN-AUTH-002', ['auth.password_set']);

    postJson('/api/password/set', setPasswordForm($token, $user->email))->assertNoContent();

    $user->refresh();
    expect(Hash::check('correct horse battery staple', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
    $scenario->assertEvents();
});

it('SCN-AUTH-008 rejects a token that is not valid', function (): void {
    $user = User::factory()->create();
    $before = $user->password;
    $scenario = scenario('SCN-AUTH-008', ['auth.password_link_rejected']);

    postJson('/api/password/set', setPasswordForm('wrong-token', $user->email))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This link is not valid or has expired.']);

    expect($user->refresh()->password)->toBe($before);
    $scenario->assertEvents();
});

it('rejects a link that was used already', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    postJson('/api/password/set', setPasswordForm($token, $user->email))->assertNoContent();

    postJson('/api/password/set', setPasswordForm($token, $user->email, 'an other long password'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This link is not valid or has expired.']);
});

it('rejects a valid token with a different email, with the same message', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $token = Password::createToken($user);

    postJson('/api/password/set', setPasswordForm($token, $other->email))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This link is not valid or has expired.']);
});

it('rejects an expired link', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    Carbon::setTestNow(now()->addMinutes(config()->integer('auth.passwords.users.expire') + 1));

    postJson('/api/password/set', setPasswordForm($token, $user->email))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This link is not valid or has expired.']);
});

it('rejects a password with fewer than 12 characters', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    postJson('/api/password/set', setPasswordForm($token, $user->email, 'short pass1'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects a password that is in a known data breach', function (): void {
    $password = 'a long but breached password';
    $hash = strtoupper(sha1($password));
    Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr($hash, 5).":42\r\n")]);
    $user = User::factory()->create();
    $token = Password::createToken($user);

    postJson('/api/password/set', setPasswordForm($token, $user->email, $password))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects two different passwords', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $form = [...setPasswordForm($token, $user->email), 'password_confirmation' => 'something else entirely'];

    postJson('/api/password/set', $form)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
