<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only these fields can change here. The email and the role are not on this list.
 */
final class UpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'timezone' => ['sometimes', 'required', 'string', 'timezone:all'],
            'theme' => ['sometimes', 'required', 'string', 'in:system,light,dark'],
        ];
    }
}
