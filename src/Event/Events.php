<?php declare(strict_types=1);

namespace Lalaz\Event;

use Lalaz\Lalaz;

/**
 * Class Events
 *
 * Provides a static interface for registering and triggering events within the
 * Lalaz framework. Utilizes the EventHub for event management, allowing events
 * to be registered with listeners and triggered either asynchronously or synchronously.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Events
{
    /**
     * Registers an event listener for a specified event.
     *
     * Binds a callable function or an instance of EventListener to an event name
     * so that it can be triggered later. This allows for event-driven programming
     * within the Lalaz framework.
     *
     * @param string $eventName The name of the event to listen for.
     * @param callable|EventListener $listener The listener to be invoked when the event is triggered.
     * @return void
     */
    public static function register(string $eventName, callable|EventListener $listener): void
    {
        $eventHub = new EventHub();
        $eventHub->register($eventName, $listener);
    }

    /**
     * Triggers an event asynchronously.
     *
     * Executes all listeners bound to the specified event name in an asynchronous manner,
     * allowing the application to continue processing without waiting for the event
     * handlers to complete.
     *
     * @param string $eventName The name of the event to trigger.
     * @param mixed $event The event data to pass to the listeners.
     * @return void
     */
    public static function trigger(string $eventName, mixed $event): void
    {
        $eventHub = new EventHub();
        $eventHub->trigger($eventName, $event, true);
    }

    /**
     * Triggers an event synchronously.
     *
     * Executes all listeners bound to the specified event name in a synchronous manner,
     * meaning the application will wait for the event handlers to complete before proceeding.
     *
     * @param string $eventName The name of the event to trigger.
     * @param mixed $event The event data to pass to the listeners.
     * @return void
     */
    public static function triggerSync(string $eventName, mixed $event): void
    {
        $eventHub = new EventHub();
        $eventHub->trigger($eventName, $event, false);
    }
}
