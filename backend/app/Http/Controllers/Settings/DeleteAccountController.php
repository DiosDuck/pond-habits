<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Account\AccountDeletion;
use App\Enums\Role;
use App\Http\Requests\DeleteAccountRequest;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Delete the own account (docs/product/settings.md). The password protects an unlocked device.
 * The only active admin cannot delete the own account: the app must keep an admin.
 */
final readonly class DeleteAccountController
{
    public function __invoke(DeleteAccountRequest $request, AccountDeletion $deletion, DomainEventLog $events, #[CurrentUser] User $user): Response
    {
        if (! Hash::check($request->string('password')->toString(), $user->password)) {
            $events->record('user.delete_refused', ['user_id' => $user->id]);

            throw ValidationException::withMessages(['password' => __('errors.password_wrong')]);
        }

        if ($user->role === Role::Admin && ! $this->otherActiveAdminExists($user)) {
            $events->record('user.delete_refused', ['user_id' => $user->id]);

            throw ValidationException::withMessages([
                'account' => __('errors.only_admin'),
            ]);
        }

        // Log out first: logout() saves the user (a new remember token). After delete()
        // that save would insert the user again.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $userId = $user->id;
        $deletion->delete($user);
        $events->record('user.deleted', ['user_id' => $userId]);

        return response()->noContent();
    }

    private function otherActiveAdminExists(User $user): bool
    {
        // About 10 users: the app checks them in PHP.
        return User::all()->contains(
            fn (User $other): bool => $other->id !== $user->id
                && $other->role === Role::Admin
                && $other->disabled_at === null,
        );
    }
}
