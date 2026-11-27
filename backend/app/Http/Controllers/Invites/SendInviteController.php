<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invites;

use App\Http\Requests\SendInviteRequest;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * An admin sends an invite (docs/product/invites.md). Route middleware: auth:sanctum, admin.
 */
final readonly class SendInviteController
{
    public function __construct(private DomainEventLog $events) {}

    public function __invoke(SendInviteRequest $request, #[CurrentUser] User $admin): JsonResponse
    {
        $email = Str::lower(trim($request->string('email')->toString()));
        $key = 'invites:'.$admin->id;
        if (RateLimiter::tooManyAttempts($key, config()->integer('auth.invites.max_per_hour'))) {
            throw new ThrottleRequestsException(
                'Too many invites. Try again later.',
                headers: ['Retry-After' => RateLimiter::availableIn($key)],
            );
        }

        if (User::query()->where('email', $email)->first() !== null) {
            $this->events->record('invite.refused', ['user_id' => $admin->id]);

            throw ValidationException::withMessages([
                'email' => __('errors.email_taken'),
            ]);
        }

        [$invite, $token] = Invite::issue($email, $admin);
        Notification::route('mail', $email)->notify(new InviteNotification($token));
        RateLimiter::hit($key, 3600);
        $this->events->record('invite.sent', ['invite_id' => $invite->id, 'user_id' => $admin->id]);

        return new JsonResponse([
            'id' => $invite->id,
            'email' => $invite->email,
            'expires_at' => $invite->expires_at->toIso8601String(),
        ], 201);
    }
}
