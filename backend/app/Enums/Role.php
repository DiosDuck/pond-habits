<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The two roles (docs/product/README.md). The API checks the role on the server.
 */
enum Role: string
{
    case Admin = 'admin';
    case User = 'user';
}
