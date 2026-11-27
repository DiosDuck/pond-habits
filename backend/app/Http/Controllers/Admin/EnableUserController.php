<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * Enable a disabled user again (docs/product/admin.md). The user can log in again.
 */
final readonly class EnableUserController
{
    public function __invoke(User $user, DomainEventLog $events, #[CurrentUser] User $admin): JsonResponse
    {
        if ($user->disabled_at !== null) {
            $user->forceFill(['disabled_at' => null])->save();
            $events->record('admin.user_enabled', ['user_id' => $user->id, 'admin_id' => $admin->id]);
        }

        return (new AdminUserResource($user))->response()->setStatusCode(200);
    }
}
