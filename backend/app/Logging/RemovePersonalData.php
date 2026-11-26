<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * Removes personal data from each log record before it is written (privacy.md item 7, ADR 0015).
 * Logs keep the user ID. Emails, IPv4 addresses and link tokens in texts are replaced.
 * Personal fields (email, name, password, token, IP, user agent) lose their value.
 * Other objects become their class name, so a model with an email is never written.
 */
final class RemovePersonalData implements ProcessorInterface
{
    /** Field names in lower case. Their values are always removed. */
    private const array PERSONAL_FIELDS = [
        'email', 'name', 'password', 'password_confirmation', 'current_password',
        'token', 'remember_token', 'ip', 'ip_address', 'user_agent',
    ];

    /** @var array<string, string> Pattern => replacement for texts. */
    private const array TEXT_PATTERNS = [
        '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i' => '[email]',
        '/([?&]token=)[^&\s"\']+/i' => '$1[removed]',
        '/\b(?:\d{1,3}\.){3}\d{1,3}\b/' => '[ip]',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        /** @var array<mixed> $context */
        $context = $this->cleanArray($record->context);
        /** @var array<mixed> $extra */
        $extra = $this->cleanArray($record->extra);

        return $record->with(message: $this->cleanText($record->message), context: $context, extra: $extra);
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private function cleanArray(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            $clean[$key] = is_string($key) && in_array(strtolower($key), self::PERSONAL_FIELDS, true)
                ? '[removed]'
                : $this->cleanValue($value);
        }

        return $clean;
    }

    private function cleanValue(mixed $value): mixed
    {
        return match (true) {
            is_string($value) => $this->cleanText($value),
            is_array($value) => $this->cleanArray($value),
            $value instanceof Throwable => $this->cleanException($value),
            is_object($value) => '[object '.$value::class.']',
            default => $value,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function cleanException(Throwable $exception): array
    {
        $clean = [
            'class' => $exception::class,
            'message' => $this->cleanText($exception->getMessage()),
            'code' => $exception->getCode(),
            'file' => $exception->getFile().':'.$exception->getLine(),
            // File and line only: the call arguments can hold personal data.
            'trace' => array_map(
                static fn (array $frame): string => ($frame['file'] ?? '[internal]').':'.($frame['line'] ?? 0),
                $exception->getTrace(),
            ),
        ];

        if ($exception->getPrevious() instanceof Throwable) {
            $clean['previous'] = $this->cleanException($exception->getPrevious());
        }

        return $clean;
    }

    private function cleanText(string $text): string
    {
        return (string) preg_replace(array_keys(self::TEXT_PATTERNS), array_values(self::TEXT_PATTERNS), $text);
    }
}
