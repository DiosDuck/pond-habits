<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Auth\UserSessions;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Change the password (docs/product/settings.md). All sessions end, this device too:
 * the user logs in again with the new password.
 */
final readonly class ChangePasswordController
{
    public function __invoke(ChangePasswordRequest $request, DomainEventLog $events, UserSessions $sessions): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            $events->record('auth.password_change_refused', ['user_id' => $user->id]);

            throw ValidationException::withMessages([
                'current_password' => 'The current password is not correct.',
            ]);
        }

        $user->forceFill([
            // The "hashed" cast hashes the password.
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
        ])->save();
        $events->record('auth.password_changed', ['user_id' => $user->id]);

        $ended = $sessions->endAll($user->id);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($ended > 0) {
            $events->record('auth.sessions_ended', ['user_id' => $user->id]);
        }

        return response()->noContent();
    }
}
