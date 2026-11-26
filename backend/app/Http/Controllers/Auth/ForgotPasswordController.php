<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\ForgotPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Request a password reset link (docs/product/password-reset.md).
 * The answer is always 204: it does not tell if the account exists.
 */
final readonly class ForgotPasswordController
{
    // The event log is a parameter, not a constructor argument: Laravel keeps one controller
    // object for the route, and a parameter is resolved again for each request.
    public function __invoke(ForgotPasswordRequest $request, DomainEventLog $events): Response
    {
        $ipKey = 'reset-ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, config()->integer('auth.reset.max_per_ip_per_hour'))) {
            throw new ThrottleRequestsException(
                'Too many requests. Try again later.',
                headers: ['Retry-After' => RateLimiter::availableIn($ipKey)],
            );
        }
        RateLimiter::hit($ipKey, 3600);

        $email = Str::lower(trim($request->string('email')->toString()));
        $user = User::query()->where('email', $email)->first();
        $active = $user instanceof User && $user->disabled_at === null;
        $events->record('auth.reset_requested', ['user_id' => $active ? $user->id : null]);

        if (! $active) {
            return response()->noContent();
        }

        // A hash of the address in the cache key: the cache has no email addresses.
        $addressKey = 'reset-address:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($addressKey, config()->integer('auth.reset.max_per_address_per_hour'))) {
            $events->record('auth.reset_limited', ['user_id' => $user->id]);

            return response()->noContent();
        }

        $user->notify(new ResetPasswordNotification(Password::createToken($user)));
        RateLimiter::hit($addressKey, 3600);
        $events->record('auth.password_link_sent', ['user_id' => $user->id]);

        return response()->noContent();
    }
}
