<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * The logged-in user. The frontend calls it at start to know if a session exists.
 */
final readonly class MeController
{
    public function __invoke(Request $request): UserResource
    {
        $user = $request->user();
        // The auth:sanctum middleware lets only logged-in users in. This check is for the type checker.
        abort_unless($user instanceof User, 401);

        return new UserResource($user);
    }
}
