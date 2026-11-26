<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Invite;
use Illuminate\Http\JsonResponse;

/**
 * The pending invites, the first to expire first (docs/product/admin.md).
 * Expired invites are not listed. The daily prune job deletes them.
 */
final readonly class ListInvitesController
{
    public function __invoke(): JsonResponse
    {
        // A few rows at most (about 10 users), so the app filters and sorts in PHP.
        $invites = Invite::all()
            ->filter(fn (Invite $invite): bool => $invite->expires_at->isFuture())
            ->sortBy(fn (Invite $invite): int => $invite->expires_at->getTimestamp())
            ->values();

        return new JsonResponse($invites->map(fn (Invite $invite): array => [
            'id' => $invite->id,
            'email' => $invite->email,
            'expires_at' => $invite->expires_at->toIso8601String(),
        ])->all());
    }
}
