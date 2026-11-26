<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Closure;
use Illuminate\Http\Request;

/**
 * Admin endpoints (docs/product/accounts.md): the server checks the role for each request.
 * Use it after "auth:sanctum".
 */
final readonly class EnsureAdmin
{
    public function __construct(private DomainEventLog $events) {}

    /**
     * The answer of the next step goes back unchanged.
     *
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user instanceof User || $user->role !== Role::Admin) {
            $this->events->record('auth.admin_denied', ['user_id' => $user instanceof User ? $user->id : null]);
            abort(403);
        }

        return $next($request);
    }
}
