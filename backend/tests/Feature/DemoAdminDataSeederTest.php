<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Invite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAdminDataSeeder;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\seed;

// The admin@demo scenario (docs/product/accounts.md): open invites and one disabled user.
// Local, CI (E2E) and staging only. Never in production.

it('gives the demo admin one disabled user and two open invites', function (): void {
    seed(DemoAdminDataSeeder::class);

    $admin = User::query()->where('email', 'admin@demo')->sole();
    $disabled = User::query()->where('email', 'disabled@demo')->sole();
    $invites = Invite::query()->get()->sortBy('email')->values();

    expect($admin->role)->toBe(Role::Admin)
        ->and($disabled->role)->toBe(Role::User)
        ->and($disabled->disabled_at)->not->toBeNull()
        ->and(Hash::check('password', $disabled->password))->toBeTrue()
        ->and($invites->pluck('email')->all())->toBe(['friend@demo', 'neighbour@demo'])
        ->and($invites->every(fn (Invite $invite): bool => $invite->invited_by === $admin->id))->toBeTrue()
        ->and($invites->every(fn (Invite $invite): bool => $invite->expires_at->isFuture()))->toBeTrue();
});

it('can run two times with the same result', function (): void {
    seed(DemoAdminDataSeeder::class);
    seed(DemoAdminDataSeeder::class);

    expect(User::count())->toBe(3)
        ->and(Invite::count())->toBe(2);
});

it('refuses to run in production', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');

    // "db:seed" refuses first (App\Console\Commands\SeedCommand), before the seeder runs.
    expect(static fn () => seed(DemoAdminDataSeeder::class))
        ->toThrow(RuntimeException::class, 'Demo users are not allowed in production.');
    // The seeder refuses too, when code calls it without "db:seed".
    expect(static fn () => (new DemoAdminDataSeeder)->run())
        ->toThrow(RuntimeException::class, 'Demo data is not allowed in production.');
    expect(User::count())->toBe(0)
        ->and(Invite::count())->toBe(0);
});

it('runs all demo seeders with "make seed"', function (): void {
    seed(DatabaseSeeder::class);

    expect(User::query()->pluck('email')->sort()->values()->all())
        ->toBe(['admin@demo', 'disabled@demo', 'new@demo'])
        ->and(Invite::count())->toBe(2);
});
