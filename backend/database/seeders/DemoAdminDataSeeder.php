<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The admin@demo scenario (docs/product/accounts.md): open invites and one disabled user.
 * Local, CI (E2E) and staging only. Never in production.
 * The invites expire after Invite::VALID_DAYS. Run "make seed" again to get new ones.
 */
final class DemoAdminDataSeeder extends Seeder
{
    public function run(): void
    {
        // Check first, so that nothing is written in production.
        if (app()->environment('production')) {
            throw new RuntimeException('Demo data is not allowed in production.');
        }

        $this->call(DemoUserSeeder::class);
        $admin = User::query()->where('email', 'admin@demo')->sole();

        $disabled = User::query()->firstOrNew(['email' => 'disabled@demo']);
        $disabled->forceFill([
            'name' => 'Demo Disabled',
            'password' => 'password',
            'role' => Role::User,
            'email_verified_at' => $disabled->email_verified_at ?? now(),
            'disabled_at' => $disabled->disabled_at ?? now(),
        ])->save();

        // Invite::issue() replaces an old invite to the same address, so a second run makes no copies.
        foreach (['friend@demo', 'neighbour@demo'] as $email) {
            Invite::issue($email, $admin);
        }
    }
}
