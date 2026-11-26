<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Create the first admin (ADR 0005, docs/product/login.md). Run it with "make admin EMAIL=...".
 * No password is in an env file: the admin sets it from the mailed link.
 */
final class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {email : The email address of the admin}
        {--name=Admin : The display name}';

    protected $description = 'Create an admin account and mail a set-password link';

    public function handle(DomainEventLog $events): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $name = trim((string) $this->option('name'));

        $validator = Validator::make(
            ['email' => $email, 'name' => $name],
            ['email' => ['required', 'email', 'max:255', 'unique:users,email'], 'name' => ['required', 'max:255']],
        );
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            // Nobody knows this password. The admin sets a new one from the link.
            'password' => Str::random(64),
            'role' => Role::Admin,
        ])->save();
        $events->record('auth.admin_created', ['user_id' => $user->id]);

        $user->notify(new SetPasswordNotification(Password::createToken($user)));
        $events->record('auth.password_link_sent', ['user_id' => $user->id]);

        $this->info('The admin account exists. A set-password link is in the mail.');

        return self::SUCCESS;
    }
}
