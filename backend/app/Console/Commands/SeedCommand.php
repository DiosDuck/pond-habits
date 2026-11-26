<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Database\Console\Seeds\SeedCommand as LaravelSeedCommand;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * "db:seed" with one more rule: it never runs in production (docs/product/accounts.md).
 * All seeders make demo data. Laravel asks "Are you sure?" in production, and "--force"
 * skips the question. This command refuses before the question, so "--force" cannot run it.
 */
#[AsCommand(name: 'db:seed')]
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
