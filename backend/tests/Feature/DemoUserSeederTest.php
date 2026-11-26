<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\seed;

// Demo users for local, CI (E2E) and staging (docs/product/accounts.md). Never in production.

it('seeds the demo admin and the demo user with the local password', function (): void {
    seed(DemoUserSeeder::class);

    $admin = User::query()->where('email', 'admin@demo')->sole();
    $user = User::query()->where('email', 'new@demo')->sole();
    expect($admin->role)->toBe(Role::Admin)
        ->and($user->role)->toBe(Role::User)
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

it('can run two times with the same result', function (): void {
    seed(DemoUserSeeder::class);
    seed(DemoUserSeeder::class);

    expect(User::count())->toBe(2);
});

it('refuses to run in production', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');

    expect(static fn () => seed(DemoUserSeeder::class))
        ->toThrow(RuntimeException::class, 'Demo users are not allowed in production.');
    expect(User::count())->toBe(0);
});
