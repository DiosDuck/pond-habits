<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Auth\UserSessions;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Disable a user (docs/product/admin.md): no login, no password reset, all sessions end at once.
 * The data stays. An admin cannot disable the own account.
 */
final readonly class DisableUserController
{
    public function __invoke(User $user, DomainEventLog $events, UserSessions $sessions, #[CurrentUser] User $admin): JsonResponse
    {
        if ($user->id === $admin->id) {
            $events->record('admin.action_refused', ['user_id' => $admin->id]);

            throw ValidationException::withMessages([
                'user' => __('errors.disable_self'),
            ]);
        }

        if ($user->disabled_at === null) {
            $user->forceFill(['disabled_at' => now()])->save();
            $events->record('admin.user_disabled', ['user_id' => $user->id, 'admin_id' => $admin->id]);

            if ($sessions->endAll($user->id) > 0) {
                $events->record('auth.sessions_ended', ['user_id' => $user->id]);
            }
        }

        return (new AdminUserResource($user))->response()->setStatusCode(200);
    }
}
