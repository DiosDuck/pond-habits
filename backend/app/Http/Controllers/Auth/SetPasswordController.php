<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\SetPasswordRequest;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Set the password from a mailed link (docs/product/login.md).
 * The Laravel password broker checks the token: it must match the email, be unused and not expired.
 */
final readonly class SetPasswordController
{
    public function __construct(private DomainEventLog $events) {}

    public function __invoke(SetPasswordRequest $request): Response
    {
        $userId = null;

        $status = Password::reset(
            [
                'email' => Str::lower($request->string('email')->toString()),
                'token' => $request->string('token')->toString(),
                'password' => $request->string('password')->toString(),
            ],
            function (User $user, string $password) use (&$userId): void {
                $user->forceFill([
                    // The "hashed" cast hashes the password.
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    // The link came to this address, so the address is verified.
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
                $userId = $user->id;
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->events->record('auth.password_link_rejected');

            throw ValidationException::withMessages([
                'token' => 'This link is not valid or has expired.',
            ]);
        }

        $this->events->record('auth.password_set', ['user_id' => $userId]);

        return response()->noContent();
    }
}
