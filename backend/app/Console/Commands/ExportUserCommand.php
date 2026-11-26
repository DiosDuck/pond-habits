<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Account\UserDataExport;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Export the data of a user who cannot log in (privacy.md item 14). Run it with "make export-user EMAIL=...".
 * The file goes to backend/storage/app/private/exports/ (git ignores it). Send it to the person, then delete it.
 */
final class ExportUserCommand extends Command
{
    protected $signature = 'app:export-user {email : The email address of the user}';

    protected $description = 'Write the data of a user to a JSON file (GDPR request)';

    public function handle(UserDataExport $export, DomainEventLog $events): int
    {
        $user = User::query()->where('email', Str::lower(trim($this->argument('email'))))->first();
        if (! $user instanceof User) {
            $this->error('No user has this email address.');

            return self::FAILURE;
        }

        $path = 'exports/'.$user->id.'-'.$export->fileName();
        Storage::disk('local')->put($path, (string) json_encode($export->for($user), JSON_PRETTY_PRINT));
        $events->record('user.data_exported', ['user_id' => $user->id, 'by' => 'operator']);

        $this->info('Export written to backend/storage/app/private/'.$path.'. Send it to the person, then delete the file.');

        return self::SUCCESS;
    }
}
