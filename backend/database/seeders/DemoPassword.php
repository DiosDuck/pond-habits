<?php

declare(strict_types=1);

namespace Database\Seeders;

use RuntimeException;

/**
 * The password of all demo users: "password" locally and in CI, a random value on staging (ADR 0012).
 */
final class DemoPassword
{
    public static function value(): string
    {
        $password = config()->string('auth.demo_password');

        if (app()->environment('staging') && $password === 'password') {
            throw new RuntimeException('Set DEMO_PASSWORD to a random value on staging.');
        }

        return $password;
    }
}
