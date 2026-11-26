<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The logged-in user, as the frontend sees it.
 *
 * @property User $resource
 */
final class UserResource extends JsonResource
{
    /**
     * No "data" wrapper: the JSON is the user object.
     *
     * @var string|null
     */
    public static $wrap;

    /**
     * @return array{id: string, name: string, email: string, role: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'role' => $this->resource->role->value,
        ];
    }
}
