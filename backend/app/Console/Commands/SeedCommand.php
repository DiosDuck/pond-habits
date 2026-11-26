<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Database\Console\Seeds\SeedCommand as LaravelSeedCommand;
use RuntimeException;

/**
 * "db:seed" with one more rule: it never runs in production (docs/product/accounts.md).
 * All seeders make demo data. Laravel asks "Are you sure?" in production, and "--force"
 * skips the question. This command refuses before the question, so "--force" cannot run it.
 * The name "db:seed" comes from the inherited signature, so this command replaces Laravel's.
 */
final class SeedCommand extends LaravelSeedCommand
{
    public function handle(): int
    {
        if ($this->laravel->environment('production')) {
            throw new RuntimeException('Demo users are not allowed in production.');
        }

        return parent::handle();
    }
}
