<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invites;

use App\Http\Requests\CheckInviteRequest;
use App\Models\Invite;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * The registration page shows the invited address (docs/product/invites.md).
 * POST, not GET: the token stays out of the URL and the server logs.
 */
final readonly class CheckInviteController
{
    public function __invoke(CheckInviteRequest $request): JsonResponse
    {
        $invite = Invite::findByToken($request->string('token')->toString());

        if (! $invite instanceof Invite) {
            throw ValidationException::withMessages([
                'token' => __('errors.invite_invalid'),
            ]);
        }

        return new JsonResponse(['email' => $invite->email]);
    }
}
