<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Daily jobs. The "scheduler" container runs "schedule:work" (compose.yaml).
// model:prune deletes the rows of each Prunable model, for example expired invites (privacy.md, item 13).
Schedule::command('model:prune')->dailyAt('03:00');
