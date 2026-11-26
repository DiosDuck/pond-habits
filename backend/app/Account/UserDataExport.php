<?php

declare(strict_types=1);

namespace App\Account;

use App\Models\User;

/**
 * The data of one user as a JSON-ready array (docs/product/settings.md, GDPR Art. 15 and 20).
 * Only the data of this user, never data of other persons. Each feature adds its own section.
 * The Settings page and the "app:export-user" command use this class.
 */
final class UserDataExport
{
    /**
     * @return array{exported_at: string, user: array<string, string|null>}
     */
    public function for(User $user): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'timezone' => $user->timezone,
                'theme' => $user->theme,
                'created_at' => $user->created_at?->toIso8601String(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
        ];
    }

    public function fileName(): string
    {
        return 'pond-habits-export-'.now()->toDateString().'.json';
    }
}
