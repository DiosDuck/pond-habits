<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Log in with email and password and start a session (docs/product/login.md, ADR 0005).
 */
final readonly class LoginController
{
    // A bcrypt hash of a random text that nobody knows. Used when the email has no account.
    private const string DUMMY_HASH = '$2y$12$tHvGD/bg/Lo8bSRyFX9yF.dQrzetwZXX8VDC0OvlCiHrOFrLR7r0i';

    public function __construct(private DomainEventLog $events) {}

    public function __invoke(LoginRequest $request): UserResource
    {
        $email = Str::lower($request->string('email')->toString());
        $password = $request->string('password')->toString();
        $key = 'login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, config()->integer('auth.login.max_attempts'))) {
            $this->events->record('auth.login_throttled');

            throw new ThrottleRequestsException(
                'Too many login attempts. Try again later.',
                headers: ['Retry-After' => RateLimiter::availableIn($key)],
            );
        }

        $user = User::query()->where('email', $email)->first();

        // Check a password also when the account does not exist, so the answer time is the same.
        $passwordOk = Hash::check($password, $user->password ?? self::DUMMY_HASH);

        if (! $user instanceof User || ! $passwordOk) {
            $this->fail($key, 'auth.login_failed', $user?->id);
        }

        if ($user->disabled_at !== null) {
            $this->fail($key, 'auth.login_refused', $user->id);
        }

        RateLimiter::clear($key);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $this->events->record('auth.logged_in', ['user_id' => $user->id]);

        return new UserResource($user);
    }

    /**
     * Count the attempt and give the same general message for each failure.
     */
    private function fail(string $key, string $event, ?string $userId): never
    {
        RateLimiter::hit($key, config()->integer('auth.login.decay_seconds'));
        $this->events->record($event, ['user_id' => $userId]);

        throw ValidationException::withMessages([
            'email' => 'The email or password is not correct.',
        ]);
    }
}
