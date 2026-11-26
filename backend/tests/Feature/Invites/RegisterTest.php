<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

// docs/product/invites.md: register from the invite link (ADR 0025).

// The fake breach list has the suffix of this one password. Each other password is "not breached".
const REGISTER_BREACHED_PASSWORD = 'a long but breached secret';

beforeEach(function (): void {
    Http::preventStrayRequests();
    $suffix = substr(strtoupper(sha1(REGISTER_BREACHED_PASSWORD)), 5);
    Http::fake(['api.pwnedpasswords.com/*' => Http::response("0000000000000000000000000000000000A:1\r\n{$suffix}:42\r\n")]);
    // Registration starts a session: the request must come from the app's own origin.
    withHeader('Referer', config()->string('app.url'));
});

/**
 * @return array<string, string>
 */
function registerForm(string $token, string $password = 'correct horse battery staple'): array
{
    return [
        'token' => $token,
        'name' => 'Friend',
        'password' => $password,
        'password_confirmation' => $password,
        'timezone' => 'Europe/Bucharest',
    ];
}

function inviteToken(string $email = 'friend@example.com'): string
{
    [, $token] = Invite::issue($email, User::factory()->admin()->create());

    return $token;
}

it('shows the invited address for a valid token', function (): void {
    postJson('/api/invites/check', ['token' => inviteToken()])
        ->assertOk()
        ->assertExactJson(['email' => 'friend@example.com']);
});

it('rejects the check of a wrong token', function (): void {
    postJson('/api/invites/check', ['token' => 'wrong-token'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This invite is not valid or has expired.']);
});

it('SCN-AUTH-009 registers from the invite and logs in', function (): void {
    $token = inviteToken();
    $scenario = scenario('SCN-AUTH-009', ['user.registered', 'auth.logged_in']);

    postJson('/api/register', registerForm($token))
        ->assertCreated()
        ->assertJsonPath('email', 'friend@example.com')
        ->assertJsonPath('name', 'Friend')
        ->assertJsonPath('role', 'user');

    $user = User::query()->where('email', 'friend@example.com')->sole();
    expect($user->role)->toBe(Role::User)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->timezone)->toBe('Europe/Bucharest')
        ->and(Hash::check('correct horse battery staple', $user->password))->toBeTrue()
        ->and(Invite::count())->toBe(0);
    getJson('/api/me')->assertOk()->assertJsonPath('id', $user->id);
    $scenario->assertEvents();
});

it('takes the address from the invite and ignores an email in the form', function (): void {
    $form = [...registerForm(inviteToken()), 'email' => 'someone-else@example.com'];

    postJson('/api/register', $form)->assertCreated()->assertJsonPath('email', 'friend@example.com');

    expect(User::query()->where('email', 'someone-else@example.com')->first())->toBeNull();
});

it('SCN-AUTH-010 rejects a token that is not valid', function (): void {
    $scenario = scenario('SCN-AUTH-010', ['invite.rejected']);

    postJson('/api/register', registerForm('wrong-token'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This invite is not valid or has expired.']);

    expect(User::count())->toBe(0);
    getJson('/api/me')->assertUnauthorized();
    $scenario->assertEvents();
});

it('rejects an invite that was used already', function (): void {
    $token = inviteToken();
    postJson('/api/register', registerForm($token))->assertCreated();
    postJson('/api/logout')->assertNoContent();

    postJson('/api/register', registerForm($token, 'an other long password'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This invite is not valid or has expired.']);
});

it('rejects an expired invite', function (): void {
    $token = inviteToken();
    Carbon::setTestNow(now()->addDays(7)->addMinute());

    postJson('/api/register', registerForm($token))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'This invite is not valid or has expired.']);

    expect(User::query()->where('email', 'friend@example.com')->first())->toBeNull();
});

it('rejects a password with fewer than 12 characters', function (): void {
    postJson('/api/register', registerForm(inviteToken(), 'short pass1'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects a password that is in a known data breach', function (): void {
    postJson('/api/register', registerForm(inviteToken(), REGISTER_BREACHED_PASSWORD))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires a name and a valid time zone', function (): void {
    $form = [...registerForm(inviteToken()), 'name' => '', 'timezone' => 'Mars/Olympus'];

    postJson('/api/register', $form)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'timezone']);
});

it('keeps the invite when the form has an error, so the user can try again', function (): void {
    $token = inviteToken();
    postJson('/api/register', registerForm($token, 'short pass1'))->assertUnprocessable();

    postJson('/api/register', registerForm($token))->assertCreated();
});
