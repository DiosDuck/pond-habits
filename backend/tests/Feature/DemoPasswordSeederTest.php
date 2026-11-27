<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\seed;

// ADR 0012: on staging, the demo users get a random password on each deploy (DEMO_PASSWORD).
// Local work and CI keep "password". Staging refuses the known local password.

const DEMO_EMAILS = ['admin@demo', 'new@demo', 'disabled@demo'];

it('gives all demo users the password from DEMO_PASSWORD', function (): void {
    config()->set('auth.demo_password', 'a random staging password 7Qx');

    seed(DatabaseSeeder::class);

    foreach (DEMO_EMAILS as $email) {
        $hash = User::query()->where('email', $email)->sole()->password;
        expect(Hash::check('a random staging password 7Qx', $hash))->toBeTrue("{$email} has the new password")
            ->and(Hash::check('password', $hash))->toBeFalse("{$email} still has the local password");
    }
});

it('changes the password of existing demo users on the next seed', function (): void {
    config()->set('auth.demo_password', 'the first deploy password');
    seed(DatabaseSeeder::class);
    config()->set('auth.demo_password', 'the second deploy password');

    seed(DatabaseSeeder::class);

    $hash = User::query()->where('email', 'admin@demo')->sole()->password;
    expect(Hash::check('the second deploy password', $hash))->toBeTrue()
        ->and(Hash::check('the first deploy password', $hash))->toBeFalse();
});

it('uses "password" when DEMO_PASSWORD is not set', function (): void {
    expect(config('auth.demo_password'))->toBe('password');
});

it('refuses to seed staging with the local password', function (): void {
    app()->detectEnvironment(static fn (): string => 'staging');
    config()->set('auth.demo_password', 'password');

    expect(static fn () => seed(DatabaseSeeder::class))
        ->toThrow(RuntimeException::class, 'Set DEMO_PASSWORD to a random value on staging.');
    expect(User::count())->toBe(0);
});
