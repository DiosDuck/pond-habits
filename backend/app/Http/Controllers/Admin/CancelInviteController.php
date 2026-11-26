<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Invite;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Cancel a pending invite (docs/product/admin.md). The link in the mail stops working.
 */
final readonly class CancelInviteController
{
    public function __invoke(Request $request, Invite $invite, DomainEventLog $events): Response
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 401);

        $invite->delete();
        $events->record('admin.invite_cancelled', ['invite_id' => $invite->id, 'admin_id' => $admin->id]);

        return response()->noContent();
    }
}
