<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;

// ADR 0021: the API docs are open on local, allowed on staging (Caddy limits them to the tailnet),
// and do not exist in production.

it('allows the API docs for a guest on staging only', function (string $environment, bool $allowed): void {
    app()->detectEnvironment(static fn (): string => $environment);

    expect(Gate::allows('viewApiDocs'))->toBe($allowed);
})->with([
    'staging' => ['staging', true],
    'production' => ['production', false],
    'testing' => ['testing', false],
]);

it('registers the API docs routes outside production', function (): void {
    $routes = app(Router::class)->getRoutes();

    expect($routes->getByName('scramble.docs.ui'))->toBeInstanceOf(Route::class)
        ->and($routes->getByName('scramble.docs.document'))->toBeInstanceOf(Route::class);
});

it('registers no API docs route in production', function (): void {
    $set = static function (string $value): void {
        putenv('APP_ENV='.$value);
        $_ENV['APP_ENV'] = $value;
        $_SERVER['APP_ENV'] = $value;
    };
    $set('production');

    try {
        $app = require __DIR__.'/../../bootstrap/app.php';
        assert($app instanceof Application);
        $app->make(Kernel::class)->bootstrap();
        $uris = array_map(
            static fn (Route $route): string => $route->uri(),
            $app->make(Router::class)->getRoutes()->getRoutes(),
        );
    } finally {
        $set('testing');
    }

    expect(in_array('health', $uris, true))->toBeTrue()
        ->and(in_array('docs/api', $uris, true))->toBeFalse()
        ->and(in_array('docs/api.json', $uris, true))->toBeFalse();
});
