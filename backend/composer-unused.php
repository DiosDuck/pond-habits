<?php

declare(strict_types=1);

// composer-unused settings (make lint-backend). Each exception has a reason.

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static fn (Configuration $config): Configuration => $config
    // It works through its service provider (package discovery): no code calls its classes.
    ->addNamedFilter(NamedFilter::fromString('sentry/sentry-laravel'));
