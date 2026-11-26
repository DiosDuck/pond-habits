<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * "make seed": demo data for local work. Each seeder refuses to run in production.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoUserSeeder::class);
    }
}
