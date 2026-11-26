<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Profile, time zone and theme (docs/product/settings.md).
 */
final readonly class SettingsController
{
    public function show(Request $request): JsonResponse
    {
        return new JsonResponse($this->settings($this->user($request)));
    }

    public function update(UpdateSettingsRequest $request, DomainEventLog $events): JsonResponse
    {
        $user = $this->user($request);
        $user->forceFill($request->validated())->save();
        $events->record('user.settings_updated', ['user_id' => $user->id]);

        return new JsonResponse($this->settings($user));
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    /**
     * @return array{name: string, email: string, timezone: string, theme: string}
     */
    private function settings(User $user): array
    {
        return ['name' => $user->name, 'email' => $user->email, 'timezone' => $user->timezone, 'theme' => $user->theme];
    }
}
