<?php

declare(strict_types=1);

use App\Support\DomainEvents\RecordingDomainEventLog;

// Architecture rules (ADR 0009). They fail the build when the code breaks a rule.

arch('PHP preset: no debug functions, no deprecated functions')->preset()->php();

arch('security preset: no eval, no md5 or sha1 for secrets, and similar')->preset()->security();

arch('Laravel preset: Laravel naming and placement')->preset()->laravel();

arch('all app code uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('the in-memory event recorder is only for tests')
    ->expect(RecordingDomainEventLog::class)
    ->toOnlyBeUsedIn('Tests');
