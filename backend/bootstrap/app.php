<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\IpUtils;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // /health has no middleware group: no session, no cookies, no database write.
        then: function (): void {
            Route::get('/health', HealthController::class)->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA cookie mode (ADR 0005): requests from the app's own origin get a session and CSRF checks.
        $middleware->statefulApi();
        // Two Caddy servers (edge and web) are in front of PHP on the server. Trust their
        // X-Forwarded-* headers only from private networks, so that the rate limits see the
        // real visitor (ADR 0015). PRIVATE_RANGES has no documentation or benchmark ranges.
        $middleware->trustProxies(at: IpUtils::PRIVATE_RANGES);
        $middleware->alias(['admin' => EnsureAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
