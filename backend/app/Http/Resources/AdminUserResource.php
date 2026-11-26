<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user as the admin pages see it (docs/product/admin.md).
 *
 * @property User $resource
 */
final class AdminUserResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap;

    /**
     * @return array{id: string, name: string, email: string, role: string, disabled: bool, joined_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'role' => $this->resource->role->value,
            'disabled' => $this->resource->disabled_at !== null,
            'joined_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
