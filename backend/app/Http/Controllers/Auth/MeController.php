<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Resources\MeResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The logged-in user. The frontend calls it at start to know if a session exists.
 */
final readonly class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        // The auth:sanctum middleware lets only logged-in users in. This check is for the type checker.
        abort_unless($user instanceof User, 401);

        // Always 200. A resource answers 201 by itself for a model that this request created.
        return (new MeResource($user))->response()->setStatusCode(200);
    }
}
