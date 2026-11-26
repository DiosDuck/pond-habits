<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invites;

use App\Enums\Role;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Invite;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Register from an invite and log in (docs/product/invites.md, ADR 0025).
 * The email comes from the invite. The invite link counts as email verification.
 */
final readonly class RegisterController
{
    public function __construct(private DomainEventLog $events) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $invite = Invite::findByToken($request->string('token')->toString());

        if (! $invite instanceof Invite || User::query()->where('email', $invite->email)->first() !== null) {
            $this->events->record('invite.rejected');

            throw ValidationException::withMessages([
                'token' => __('errors.invite_invalid'),
            ]);
        }

        $user = DB::transaction(function () use ($invite, $request): User {
            $user = new User;
            $user->forceFill([
                'name' => $request->string('name')->trim()->toString(),
                'email' => $invite->email,
                // The "hashed" cast hashes the password.
                'password' => $request->string('password')->toString(),
                'role' => Role::User,
                'timezone' => $request->string('timezone')->toString(),
                'email_verified_at' => now(),
            ])->save();
            $invite->delete();

            return $user;
        });
        $this->events->record('user.registered', ['user_id' => $user->id]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $this->events->record('auth.logged_in', ['user_id' => $user->id]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }
}
