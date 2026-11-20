<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;

it('gives a new user a UUIDv7 primary key', function (): void {
    $user = User::factory()->create();

    expect(Str::isUuid($user->id, 7))->toBeTrue();
});

it('makes UUIDs that sort in creation order', function (): void {
    $first = User::factory()->create();
    $second = User::factory()->create();

    expect(strcmp($first->id, $second->id))->toBeLessThan(0);
});
