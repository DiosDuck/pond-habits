<?php

declare(strict_types=1);

// Rector runs in check mode only (ADR 0009): "make lint" fails when Rector finds a change.
// The developer applies the change on purpose.

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    // Config files must stay cacheable (php artisan config:cache): no closures there.
    ->withSkip([ArrayToFirstClassCallableRector::class => [__DIR__.'/config']])
    ->withCache(__DIR__.'/storage/framework/cache/rector');
