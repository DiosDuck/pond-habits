<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Account\UserDataExport;
use App\Models\User;
use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Download the own data as a JSON file (docs/product/settings.md).
 */
final readonly class ExportController
{
    public function __invoke(Request $request, UserDataExport $export, DomainEventLog $events): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $events->record('user.data_exported', ['user_id' => $user->id]);

        return new JsonResponse($export->for($user), 200, [
            'Content-Disposition' => 'attachment; filename='.$export->fileName(),
        ], JSON_PRETTY_PRINT);
    }
}
