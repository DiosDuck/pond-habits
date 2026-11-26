<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * An invite to register (docs/product/invites.md).
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

    /**
     * Make an invite and return it with the plain token for the mail.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(string $email, User $invitedBy): array
    {
        throw new LogicException('Not done yet.');
    }

    public static function findByToken(string $token): ?self
    {
        throw new LogicException('Not done yet.');
    }
}
