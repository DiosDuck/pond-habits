<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo users for local, CI (E2E) and staging (docs/product/accounts.md). Never in production.
 * The local password is "password". Staging sets random passwords (Phase 4).
 * More demo users come with their features.
 */
final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo users are not allowed in production.');
        }

        $this->demoUser('admin@demo', 'Demo Admin', Role::Admin);
        $this->demoUser('new@demo', 'Demo New', Role::User);
    }

    private function demoUser(string $email, string $name, Role $role): void
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'role' => $role,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'disabled_at' => null,
        ])->save();
    }
}
