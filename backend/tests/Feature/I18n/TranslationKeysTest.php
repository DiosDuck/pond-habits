<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

// User texts come from translation keys in lang/en (ADR 0001: i18n keys from day 1).
// These checks read the source code of app/, so they also cover code that no other test runs.

/**
 * @return array<string, string> The PHP files of app/: relative path => source code.
 */
function appSourceFiles(): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
            $files[str_replace(app_path().'/', '', $file->getPathname())] = (string) file_get_contents($file->getPathname());
        }
    }

    return $files;
}

it('finds each translation key of the app code in lang/en', function (): void {
    $found = [];
    $missing = [];

    foreach (appSourceFiles() as $path => $code) {
        preg_match_all("/\\b(?:__|trans|trans_choice)\\(\\s*'([^']+)'/", $code, $matches);

        foreach ($matches[1] as $key) {
            $found[] = $key;

            if (! Lang::has($key, 'en', false)) {
                $missing[] = "{$path}: {$key}";
            }
        }
    }

    expect($found)->not->toBeEmpty()
        ->and($missing)->toBe([]);
});

it('has no user text typed into mails or error messages', function (): void {
    $patterns = [
        // A mail text: ->subject('...'), ->line("..."), ->action('...', $url) and similar.
        '/->(?:subject|greeting|line|action|salutation)\(\s*[\'"]/',
        // An error text: ValidationException::withMessages(['field' => '...']).
        '/withMessages\(\s*\[[^\]]*=>\s*[\'"]/s',
    ];
    $typed = [];

    foreach (appSourceFiles() as $path => $code) {
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $code, $matches) > 0) {
                foreach ($matches[0] as $match) {
                    $typed[] = "{$path}: {$match}";
                }
            }
        }
    }

    expect($typed)->toBe([]);
});
