<?php

declare(strict_types=1);

use App\Support\Alerts\Alerts;
use App\Support\Alerts\SentryAlerts;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;

// ADR 0014: a warning (mail cap, failed-login spike) is a Sentry event with the level "warning".
// Sentry groups the events of one warning type and emails the owner. The extra data holds counts
// and types only, never personal data.

/**
 * A Sentry client that keeps the events in memory, in place of the network.
 *
 * @return ArrayObject<int, Event>
 */
function sentryOutbox(): ArrayObject
{
    /** @var ArrayObject<int, Event> $outbox */
    $outbox = new ArrayObject;
    $transport = new readonly class($outbox) implements TransportInterface
    {
        /** @param  ArrayObject<int, Event>  $outbox */
        public function __construct(private ArrayObject $outbox) {}

        public function send(Event $event): Result
        {
            $this->outbox->append($event);

            return new Result(ResultStatus::success(), $event);
        }

        public function close(?int $timeout = null): Result
        {
            return new Result(ResultStatus::success());
        }
    };
    SentrySdk::getCurrentHub()->bindClient(
        ClientBuilder::create(['dsn' => 'https://public@example.com/1'])->setTransport($transport)->getClient(),
    );

    return $outbox;
}

it('is the alert channel of the app', function (): void {
    expect(app(Alerts::class))->toBeInstanceOf(SentryAlerts::class);
});

it('sends a warning event, grouped by the warning type, with the context as extra data', function (): void {
    $outbox = sentryOutbox();

    app(SentryAlerts::class)->warn('mail_cap_close', 'The daily mail cap is nearly used.', ['used' => 4, 'cap' => 5]);

    expect($outbox)->toHaveCount(1);
    $event = $outbox[0];
    assert($event instanceof Event);
    expect($event->getLevel())->toEqual(Severity::warning())
        ->and($event->getMessage())->toBe('The daily mail cap is nearly used.')
        ->and($event->getFingerprint())->toBe(['pond-alert', 'mail_cap_close'])
        ->and($event->getExtra())->toBe(['used' => 4, 'cap' => 5]);
});
