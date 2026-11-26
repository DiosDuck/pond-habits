<?php

declare(strict_types=1);

namespace App\Account;

use App\Auth\UserSessions;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Delete a user and all data of the user at once (docs/product/settings.md, privacy.md item 2).
 * The foreign keys delete the rows of the user (cascade). The sessions have no foreign key.
 * The Settings page and the "app:delete-user" command use this class.
 */
final readonly class AccountDeletion
{
    public function __construct(private UserSessions $sessions) {}

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->sessions->endAll($user->id);
            $user->delete();
        });
    }
}
