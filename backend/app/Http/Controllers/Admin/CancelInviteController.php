<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Invite;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

/**
 * Cancel a pending invite (docs/product/admin.md). The link in the mail stops working.
 */
final readonly class CancelInviteController
{
    public function __invoke(Invite $invite, DomainEventLog $events, #[CurrentUser] User $admin): Response
    {
        $invite->delete();
        $events->record('admin.invite_cancelled', ['invite_id' => $invite->id, 'admin_id' => $admin->id]);

        return response()->noContent();
    }
}
