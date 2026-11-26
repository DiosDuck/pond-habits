<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as Monolog;

/**
 * A Laravel log "tap": it adds RemovePersonalData to a channel (config/logging.php).
 * The processor that is added last runs first, so it cleans the record before
 * the placeholder processor writes context values into the message.
 */
final class AddPersonalDataProcessor
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if ($monolog instanceof Monolog) {
            $monolog->pushProcessor(new RemovePersonalData);
        }
    }
}
