<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The users list of the admin pages, the oldest account first. About 10 users: no pages, sorted in PHP.
 */
final readonly class ListUsersController
{
    public function __invoke(Request $request): JsonResponse
    {
        $users = User::all()->sortBy(fn (User $user): int => $user->created_at?->getTimestamp() ?? 0)->values();

        return new JsonResponse(
            $users->map(fn (User $user): array => (new AdminUserResource($user))->toArray($request))->all(),
        );
    }
}
