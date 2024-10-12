<?php declare(strict_types=1);

namespace Lalaz\Event;

use Lalaz\Event\Events;
use Lalaz\Queue\Job;

/**
 * Class EventJob
 *
 * Represents a queued event that will be processed asynchronously.
 * When executed, it triggers the original event and all its listeners.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class EventJob extends Job
{
    /**
     * Handle the execution of the event job.
     *
     * This method retrieves the event data, deserializes it, and triggers
     * it to the relevant listeners synchronously.
     *
     * @param array $payload The data required to process the event.
     * @return void
     */
    public function handle(array $payload): void
    {
        $eventName = $payload['event_name'] ?? null;
        $eventData = isset($payload['event_data']) ? unserialize($payload['event_data']) : null;

        $eventHub = new EventHub();

        if ($eventName && $eventData) {
            $eventHub->triggerSync($eventName, $eventData);
        }
    }
}
