<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Support\Facades\DB;

/**
 * The server sessions of a user (Sanctum SPA cookie mode, ADR 0005).
 * The "database" session driver keeps one row for each logged-in device.
 */
final class UserSessions
{
    /**
     * End all sessions of the user, on all devices. Returns the number of ended sessions.
     */
    public function endAll(string $userId): int
    {
        return DB::table(config()->string('session.table'))->where('user_id', $userId)->delete();
    }
}
