<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Feature tests boot Laravel and use the real PostgreSQL test database (ADR 0004, ADR 0008).
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
