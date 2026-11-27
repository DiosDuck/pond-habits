<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Resources\MeResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * The logged-in user. The frontend calls it at start to know if a session exists.
 */
final readonly class MeController
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        // Always 200. A resource answers 201 by itself for a model that this request created.
        return (new MeResource($user))->response()->setStatusCode(200);
    }
}
