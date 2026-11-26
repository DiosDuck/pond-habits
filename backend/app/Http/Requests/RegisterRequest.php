<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Registration from an invite (docs/product/invites.md). The email is not an input:
 * the server takes it from the invite.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Password>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)->uncompromised()],
            'timezone' => ['required', 'string', 'timezone:all'],
        ];
    }
}
