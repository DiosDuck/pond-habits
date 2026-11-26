<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

// docs/product/login.md: the first admin comes from a command, not from an env file (ADR 0005).

beforeEach(function (): void {
    Notification::fake();
});

it('SCN-AUTH-001 creates the first admin and mails a set-password link', function (): void {
    $scenario = scenario('SCN-AUTH-001', ['auth.admin_created', 'auth.password_link_sent']);

    expect(Artisan::call('app:create-admin', ['email' => 'owner@example.com']))->toBe(0);

    $user = User::query()->where('email', 'owner@example.com')->sole();
    expect($user->role)->toBe(Role::Admin)
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo(
        $user,
        SetPasswordNotification::class,
        function (SetPasswordNotification $notification) use ($user): bool {
            $url = (string) $notification->toMail($user)->actionUrl;

            // The link has the token only. The email address is not in the URL.
            return str_starts_with($url, config()->string('app.url').'/set-password?token=')
                && ! str_contains($url, 'owner')
                && ! str_contains($url, 'example.com');
        },
    );
    $scenario->assertEvents();
});

it('refuses an email address that has an account already', function (): void {
    User::factory()->create(['email' => 'owner@example.com']);

    expect(Artisan::call('app:create-admin', ['email' => 'owner@example.com']))->not->toBe(0);

    expect(User::count())->toBe(1);
    Notification::assertNothingSent();
});

it('refuses a text that is not an email address', function (): void {
    expect(Artisan::call('app:create-admin', ['email' => 'not-an-email']))->not->toBe(0);

    expect(User::count())->toBe(0);
    Notification::assertNothingSent();
});
