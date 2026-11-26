<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class SetPasswordRequest extends FormRequest
{
    /**
     * Password rules (ADR 0005): 12 characters or more, not in a known data breach.
     * The breach check sends only the first 5 characters of the SHA-1 hash (k-anonymity).
     *
     * @return array<string, list<string|Password>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)->uncompromised()],
        ];
    }
}
