<?php declare(strict_types=1);

namespace Lalaz\Event;

use Lalaz\Queue\QueueManager;

/**
 * Class EventHub
 *
 * Manages the registration and triggering of events throughout the application.
 * It allows events to be triggered either synchronously or asynchronously, with
 * asynchronous events being queued for later processing.
 *
  * @author  Elasticmind
 * @namespace Lalaz\Event
 * @package  elasticmind\lalaz-framework
 * @link     https://lalaz.dev
 */
class EventHub
{
    /**
     * @var array<string, callable[]> A list of registered event listeners.
     */
    private array $listeners = [];

    /**
     * @var QueueManager The queue manager for handling asynchronous events.
     */
    private QueueManager $queueManager;

    /**
     * EventHub constructor.
     *
     * Initializes the event hub and sets up the queue manager.
     */
    public function __construct()
    {
        $this->queueManager = new QueueManager();
        $this->emitInitializationEvent();
    }

    /**
     * Register a listener for a specific event.
     *
     * @param string $eventName The name of the event.
     * @param callable|Listener $listener The listener callback or class instance.
     * @return void
     */
    public function register(string $eventName, callable|Listener $listener): void
    {
        if ($listener instanceof Listener) {
            $this->listeners[$eventName][] = [$listener, 'handle'];
        } else {
            $this->listeners[$eventName][] = $listener;
        }
    }

    /**
     * Trigger an event, invoking all associated listeners.
     *
     * @param string $eventName The name of the event to trigger.
     * @param mixed $event The event object or data to pass to the listeners.
     * @param bool $async Determines if the event should be processed asynchronously.
     * @return void
     */
    public function trigger(string $eventName, mixed $event, bool $async = false): void
    {
        if ($async) {
            $this->queueManager->addJob(EventJob::class, [
                'event_name' => $eventName,
                'event_data' => serialize($event),
            ]);
        } else {
            $this->triggerSync($eventName, $event);
        }
    }

    /**
     * Trigger the event synchronously.
     *
     * @param string $eventName The name of the event.
     * @param mixed $event The event object or data to pass to the listeners.
     * @return void
     */
    public function triggerSync(string $eventName, mixed $event): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);
        }
    }

    private function emitInitializationEvent(): void
    {
        if (function_exists('onEventHubInitialized')) {
            onEventHubInitialized($this);
        }
    }
}
