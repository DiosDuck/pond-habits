<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * GET /health: the app runs and the database answers.
 * Docker, the deploy smoke test and the uptime monitor use it. It has no session and no auth.
 */
final class HealthController
{
    public function __invoke(ConnectionInterface $db): JsonResponse
    {
        try {
            $db->select('select 1');
        } catch (Throwable) {
            return new JsonResponse(['status' => 'fail', 'checks' => ['database' => 'fail']], 503);
        }

        return new JsonResponse(['status' => 'ok', 'checks' => ['database' => 'ok']]);
    }
}
