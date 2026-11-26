<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An invite to register (docs/product/invites.md, ADR 0025).
 * The table has a SHA-256 hash of the token. The plain token is only in the mail.
 *
 * @property string $id
 * @property string $email
 * @property string $token_hash
 * @property string $invited_by
 * @property Carbon $expires_at
 */
final class Invite extends Model
{
    use HasUuids;
    use MassPrunable;

    public const int VALID_DAYS = 7;

    /**
     * Make an invite and return it with the plain token for the mail.
     * A new invite to the same address replaces the old one.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(string $email, User $invitedBy): array
    {
        $email = Str::lower(trim($email));
        $token = Str::random(64);

        self::query()->where('email', $email)->delete();

        $invite = new self;
        $invite->forceFill([
            'email' => $email,
            'token_hash' => self::hashToken($token),
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(self::VALID_DAYS),
        ])->save();

        return [$invite, $token];
    }

    /**
     * The invite of a token, when it exists and has not expired.
     */
    public static function findByToken(string $token): ?self
    {
        return self::query()
            ->where('token_hash', self::hashToken($token))
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * The daily "model:prune" job deletes expired invites (privacy.md, item 13).
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<=', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    private static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
