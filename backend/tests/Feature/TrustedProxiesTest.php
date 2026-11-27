<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\withServerVariables;

// On the server, two Caddy servers (edge and web) are in front of PHP (infra/deploy). PHP sees the
// address of a Caddy container. Laravel trusts X-Forwarded-For only from private networks (the Docker
// networks), so the rate limits count the real visitor (ADR 0015). A visitor cannot fake the address.

// A private address for the Caddy container. IPv6 (unique local), because scripts/sensitive-check.sh
// refuses private IPv4 addresses in the repo. The rule is the same for both.
const DOCKER_CADDY = 'fd12:3456:789a::5';

beforeEach(function (): void {
    Route::get('/test/client', static fn (Request $request): array => [
        'ip' => $request->ip(),
        'secure' => $request->isSecure(),
    ]);
});

it('uses the visitor address that the Caddy containers forward', function (): void {
    withServerVariables(['REMOTE_ADDR' => DOCKER_CADDY])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.9, fd12:3456:789a::2', 'X-Forwarded-Proto' => 'https'])
        ->getJson('/test/client')
        ->assertExactJson(['ip' => '203.0.113.9', 'secure' => true]);
});

it('ignores a forwarded address that a visitor sends directly', function (): void {
    withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])
        ->getJson('/test/client')
        ->assertExactJson(['ip' => '198.51.100.7', 'secure' => false]);
});

it('ignores a fake address at the start of the chain', function (): void {
    // The visitor sent "X-Forwarded-For: 192.0.2.66"; the edge Caddy added the real address.
    withServerVariables(['REMOTE_ADDR' => DOCKER_CADDY])
        ->withHeaders(['X-Forwarded-For' => '192.0.2.66, 203.0.113.9'])
        ->getJson('/test/client')
        ->assertJsonPath('ip', '203.0.113.9');
});

it('counts failed logins for each visitor, not for the Caddy container', function (): void {
    config()->set('auth.login.max_attempts', 2);
    $user = User::factory()->create(['password' => 'a good long password']);
    $wrong = ['email' => $user->email, 'password' => 'a wrong password'];
    $from = static fn (string $visitor) => withServerVariables(['REMOTE_ADDR' => DOCKER_CADDY])
        ->withHeaders(['X-Forwarded-For' => $visitor, 'Referer' => config()->string('app.url')]);

    $from('203.0.113.9')->postJson('/api/login', $wrong)->assertUnprocessable();
    $from('203.0.113.9')->postJson('/api/login', $wrong)->assertUnprocessable();
    $from('203.0.113.9')->postJson('/api/login', $wrong)->assertTooManyRequests();

    $from('198.51.100.20')->postJson('/api/login', $wrong)->assertUnprocessable();
});
