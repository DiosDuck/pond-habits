<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

// docs/product/settings.md, privacy.md item 14: the operator exports and deletes the data of a
// user who cannot log in. The commands use the same code as the Settings page.

beforeEach(function (): void {
    Storage::fake('local');
});

it('SCN-SET-008 exports and then deletes a disabled user', function (): void {
    $user = User::factory()->disabled()->create(['email' => 'bo@example.com']);
    $scenario = scenario('SCN-SET-008', ['user.data_exported', 'user.deleted']);

    expect(Artisan::call('app:export-user', ['email' => 'bo@example.com']))->toBe(0);
    $files = Storage::disk('local')->files('exports');
    expect($files)->toHaveCount(1);
    $export = json_decode((string) Storage::disk('local')->get($files[0]), true);
    expect($export)->toBeArray()->toHaveKey('user.email', 'bo@example.com');

    expect(Artisan::call('app:delete-user', ['email' => 'bo@example.com', '--force' => true]))->toBe(0)
        ->and(User::query()->whereKey($user->id)->first())->toBeNull();
    $scenario->assertEvents();
});

it('does not delete without --force when nobody answers the question', function (): void {
    User::factory()->create(['email' => 'bo@example.com']);

    expect(Artisan::call('app:delete-user', ['email' => 'bo@example.com', '--no-interaction' => true]))->not->toBe(0)
        ->and(User::query()->where('email', 'bo@example.com')->first())->not->toBeNull();
});

it('fails for an unknown address', function (): void {
    expect(Artisan::call('app:export-user', ['email' => 'nobody@example.com']))->not->toBe(0)
        ->and(Artisan::call('app:delete-user', ['email' => 'nobody@example.com', '--force' => true]))->not->toBe(0)
        ->and(Storage::disk('local')->files('exports'))->toBe([]);
});
