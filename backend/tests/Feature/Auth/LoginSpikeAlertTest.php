<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Alerts\Alerts;
use App\Support\Alerts\RecordingAlerts;

use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;
use function Pest\Laravel\withHeader;

// ADR 0014: until Grafana comes, the app sends one Sentry warning when the failed logins of all
// visitors reach a threshold in a time window. It can show a password-guessing attack.

beforeEach(function (): void {
    // The windows are fixed blocks of time (for example 10:00 to 10:15). Start at the start of
    // a block, so a test cannot cross a border by chance.
    travelTo(now()->startOfHour());
    withHeader('Referer', config()->string('app.url'));
    config()->set('auth.alerts.failed_logins', ['threshold' => 3, 'window_minutes' => 15]);
});

function spikeAlerts(): RecordingAlerts
{
    $alerts = new RecordingAlerts;
    app()->instance(Alerts::class, $alerts);

    return $alerts;
}

/**
 * One failed login from its own email address, so the login throttle (per email and address)
 * does not stop the test.
 */
function failLogin(int $number): void
{
    postJson('/api/login', ['email' => "nobody{$number}@example.com", 'password' => 'a wrong password'])
        ->assertUnprocessable();
}

it('warns once when the failed logins reach the threshold in the window', function (): void {
    $alerts = spikeAlerts();

    failLogin(1);
    failLogin(2);
    expect($alerts->keys())->toBe([]);

    failLogin(3);
    failLogin(4);
    failLogin(5);

    expect($alerts->keys())->toBe(['login_failures_spike'])
        ->and($alerts->contexts()[0])->toBe(['failed_logins' => 3, 'window_minutes' => 15]);
});

it('warns again in the next window', function (): void {
    $alerts = spikeAlerts();
    failLogin(1);
    failLogin(2);
    failLogin(3);

    travel(16)->minutes();
    failLogin(4);
    failLogin(5);
    failLogin(6);

    expect($alerts->keys())->toBe(['login_failures_spike', 'login_failures_spike']);
});

it('does not count successful logins', function (): void {
    $alerts = spikeAlerts();
    $user = User::factory()->create(['password' => 'a good long password']);

    failLogin(1);
    failLogin(2);
    postJson('/api/login', ['email' => $user->email, 'password' => 'a good long password'])->assertOk();

    expect($alerts->keys())->toBe([]);
});
