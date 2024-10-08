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
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
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
     * @param callable|EventListener $listener The listener callback or class instance.
     * @return void
     */
    public function register(string $eventName, callable|EventListener $listener): void
    {
        if ($listener instanceof EventListener) {
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

    /**
     * Checks if an event has registered listeners.
     *
     * @param string $eventName The name of the event to check.
     * @return bool True if listeners are registered, false otherwise.
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Emit the initialization event to allow customization.
     *
     * @return void
     */
    private function emitInitializationEvent(): void
    {
        if (function_exists('onEventHubInitialized')) {
            onEventHubInitialized($this);
        }
    }
}
