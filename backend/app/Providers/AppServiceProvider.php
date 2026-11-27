<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Alerts\Alerts;
use App\Support\Alerts\SentryAlerts;
use App\Support\DomainEvents\DomainEventLog;
use App\Support\DomainEvents\LogDomainEventLog;
use Dedoc\Scramble\Scramble;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Sentry\State\Scope;

use function Sentry\configureScope;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            DomainEventLog::class,
            static fn (): DomainEventLog => new LogDomainEventLog(Log::channel('domain')),
        );

        $this->app->singleton(Alerts::class, SentryAlerts::class);

        // ADR 0021: the API docs route does not exist in production. It is disabled in the code.
        if ($this->app->environment('production')) {
            Scramble::ignoreDefaultRoutes();
        }
    }

    public function boot(): void
    {
        // Local: Scramble always allows the docs. Staging: allowed here, and Caddy limits it to the tailnet.
        // The nullable $user lets Laravel call the gate for guests. Without it, guests are always denied.
        Gate::define('viewApiDocs', fn (?User $user = null): bool => $this->app->environment('staging'));

        // Error reports (ADR 0014) know the user by ID only: never the email or the name.
        Event::listen(Authenticated::class, static function (Authenticated $event): void {
            $id = $event->user->getAuthIdentifier();
            configureScope(static fn (Scope $scope): Scope => $scope->setUser(['id' => is_scalar($id) ? (string) $id : null]));
        });
    }
}
