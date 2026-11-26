<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Account\AccountDeletion;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Delete a user who cannot log in, with all the data (privacy.md item 14).
 * Run it with "make delete-user EMAIL=...". It asks first. Without an answer it deletes nothing.
 */
final class DeleteUserCommand extends Command
{
    protected $signature = 'app:delete-user
        {email : The email address of the user}
        {--force : Delete without the question}';

    protected $description = 'Delete a user and all data of the user (GDPR request). This cannot be undone.';

    public function handle(AccountDeletion $deletion, DomainEventLog $events): int
    {
        $user = User::query()->where('email', Str::lower(trim($this->argument('email'))))->first();
        if (! $user instanceof User) {
            $this->error('No user has this email address.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Delete this user and all data? This cannot be undone.')) {
            $this->warn('Nothing deleted.');

            return self::FAILURE;
        }

        $userId = $user->id;
        $deletion->delete($user);
        $events->record('user.deleted', ['user_id' => $userId, 'by' => 'operator']);
        $this->info('The user and all data of the user are deleted.');

        return self::SUCCESS;
    }
}
