<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The logged-in user for GET /api/me: the user fields plus the time zone and the theme.
 *
 * @property User $resource
 */
final class MeResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap;

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new UserResource($this->resource))->toArray($request),
            'timezone' => $this->resource->timezone,
            'theme' => $this->resource->theme,
        ];
    }
}
