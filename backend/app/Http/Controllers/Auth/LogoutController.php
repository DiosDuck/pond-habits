<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Log out: end the server session (docs/product/login.md).
 */
final readonly class LogoutController
{
    public function __construct(private DomainEventLog $events) {}

    public function __invoke(Request $request): Response
    {
        $userId = Auth::id();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $this->events->record('auth.logged_out', ['user_id' => is_string($userId) ? $userId : null]);

        return response()->noContent();
    }
}
