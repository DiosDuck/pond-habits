<?php

declare(strict_types=1);

use App\Support\DomainEvents\DomainEventLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Privacy baseline item 8: logs are deleted after 14 days. On the server (LOG_DOMAIN_DRIVER=daily),
// the domain event log writes one file for each day and deletes the files older than 14 days.
// Local work and the other tests keep one file (the default "single").

/**
 * Read the "domain" channel from config/logging.php with LOG_DOMAIN_DRIVER set, as on the server.
 *
 * @return array<array-key, mixed>
 */
function serverDomainChannel(): array
{
    $_SERVER['LOG_DOMAIN_DRIVER'] = $_ENV['LOG_DOMAIN_DRIVER'] = 'daily';

    try {
        $config = require config_path('logging.php');
    } finally {
        unset($_SERVER['LOG_DOMAIN_DRIVER'], $_ENV['LOG_DOMAIN_DRIVER']);
    }

    expect($config)->toBeArray();
    assert(is_array($config) && is_array($config['channels']) && is_array($config['channels']['domain']));

    return $config['channels']['domain'];
}

it('uses the daily driver with 14 files when LOG_DOMAIN_DRIVER is daily', function (): void {
    $channel = serverDomainChannel();

    // toEqual: the value is the int 14, or the text "14" when LOG_DAILY_DAYS is set.
    expect($channel['driver'])->toBe('daily')
        ->and($channel['max_files'])->toEqual(14);
});

it('writes a file for each day and deletes the files older than 14 days', function (): void {
    $dir = storage_path('logs/domain-retention-'.Str::random(8));
    mkdir($dir);
    config()->set('logging.channels.domain', [...serverDomainChannel(), 'path' => $dir.'/domain.log']);
    Log::forgetChannel('domain');
    // 15 files from the last 15 days, before today's file exists.
    foreach (range(1, 15) as $daysAgo) {
        touch($dir.'/domain-'.date('Y-m-d', strtotime("-{$daysAgo} days")).'.log');
    }

    app(DomainEventLog::class)->record('pomodoro.completed', ['user_id' => 'u-1']);

    $paths = glob($dir.'/domain-*.log');
    assert(is_array($paths));
    $files = array_map(basename(...), $paths);
    $today = (string) file_get_contents($dir.'/domain-'.date('Y-m-d').'.log');
    array_map(unlink(...), $paths);
    rmdir($dir);

    expect($files)->toHaveCount(14);
    expect($files)->toContain('domain-'.date('Y-m-d').'.log', 'domain-'.date('Y-m-d', strtotime('-13 days')).'.log');
    expect($files)->not->toContain('domain-'.date('Y-m-d', strtotime('-14 days')).'.log');
    expect($files)->not->toContain('domain-'.date('Y-m-d', strtotime('-15 days')).'.log');
    expect($today)->toContain('"message":"pomodoro.completed"');
});
