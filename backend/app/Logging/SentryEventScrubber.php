<?php

declare(strict_types=1);

namespace App\Logging;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\UserDataBag;

/**
 * Cleans each Sentry event before it is sent (config/sentry.php "before_send", ADR 0014).
 * The same rules as the logs (RemovePersonalData, privacy.md item 7): texts lose emails, IP
 * addresses and link tokens; personal fields lose their values. The request keeps only the
 * method and the path. The user keeps only the ID.
 */
final class SentryEventScrubber
{
    public static function scrub(Event $event): Event
    {
        $clean = new RemovePersonalData;

        $message = $event->getMessage();
        if ($message !== null) {
            $event->setMessage($clean->cleanText($message));
        }

        foreach ($event->getExceptions() as $exception) {
            self::cleanException($exception, $clean);
        }

        $event->setBreadcrumb(array_map(
            static fn (Breadcrumb $breadcrumb): Breadcrumb => new Breadcrumb(
                $breadcrumb->getLevel(),
                $breadcrumb->getType(),
                $breadcrumb->getCategory(),
                $breadcrumb->getMessage() === null ? null : $clean->cleanText($breadcrumb->getMessage()),
                self::stringKeys($clean->cleanArray($breadcrumb->getMetadata())),
                $breadcrumb->getTimestamp(),
            ),
            $event->getBreadcrumbs(),
        ));

        $event->setExtra(self::stringKeys($clean->cleanArray($event->getExtra())));
        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, self::stringKeys($clean->cleanArray($context)));
        }

        $event->setRequest(self::cleanRequest($event->getRequest()));

        $user = $event->getUser();
        $event->setUser($user?->getId() === null ? null : UserDataBag::createFromUserIdentifier($user->getId()));

        return $event;
    }

    private static function cleanException(ExceptionDataBag $exception, RemovePersonalData $clean): void
    {
        $exception->setValue($clean->cleanText($exception->getValue()));
        // The values of local variables can hold personal data.
        foreach ($exception->getStacktrace()?->getFrames() ?? [] as $frame) {
            $frame->setVars([]);
        }
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private static function cleanRequest(array $request): array
    {
        $kept = [];
        if (isset($request['method'])) {
            $kept['method'] = $request['method'];
        }
        if (isset($request['url']) && is_string($request['url'])) {
            $kept['url'] = strtok($request['url'], '?');
        }

        return $kept;
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private static function stringKeys(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }
}
