<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * Profile, time zone and theme (docs/product/settings.md).
 */
final readonly class SettingsController
{
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse($this->settings($user));
    }

    public function update(UpdateSettingsRequest $request, DomainEventLog $events, #[CurrentUser] User $user): JsonResponse
    {
        $user->forceFill($request->validated())->save();
        $events->record('user.settings_updated', ['user_id' => $user->id]);

        return new JsonResponse($this->settings($user));
    }

    /**
     * @return array{name: string, email: string, timezone: string, theme: string}
     */
    private function settings(User $user): array
    {
        return ['name' => $user->name, 'email' => $user->email, 'timezone' => $user->timezone, 'theme' => $user->theme];
    }
}
