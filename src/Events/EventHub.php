<?php declare(strict_types=1);

namespace Lalaz\Events;

use Lalaz\Queue\QueueManager;

/**
 * Class EventHub
 *
 * Event management system for registering and triggering events throughout the application.
 * Supports both synchronous and asynchronous event processing, with asynchronous events
 * being queued for background execution.
 *
 * Example usage:
 * ```php
 * // Configure event listeners on initialization
 * EventHub::onInitialized(function($hub) {
 *     $hub->register('user.created', new SendWelcomeEmailListener());
 *     $hub->register('order.placed', function($order) {
 *         // Handle order event
 *     });
 * });
 *
 * // Trigger events synchronously
 * $eventHub->trigger('user.created', $user);
 *
 * // Trigger events asynchronously (queued)
 * $eventHub->trigger('email.send', $emailData, async: true);
 * ```
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class EventHub
{
    /**
     * Registered event listeners indexed by event name.
     *
     * @var array<string, callable[]>
     */
    private array $listeners = [];

    /**
     * Queue manager for handling asynchronous event processing.
     *
     * @var QueueManager
     */
    private QueueManager $queueManager;

    /**
     * Initialization callback to be executed when EventHub is constructed.
     *
     * @var \Closure|null
     */
    private static ?\Closure $initializationCallback = null;

    /**
     * EventHub constructor.
     *
     * Initializes the event hub, sets up the queue manager for asynchronous events,
     * and executes any registered initialization callbacks.
     */
    public function __construct()
    {
        $this->queueManager = new QueueManager();
        $this->emitInitializationEvent();
    }

    /**
     * Register a listener for a specific event.
     *
     * Listeners can be either callable functions/closures or instances of EventListener.
     * Multiple listeners can be registered for the same event and will be executed
     * in the order they were registered.
     *
     * @param string $eventName The name of the event to listen for (e.g., 'user.created', 'order.completed').
     * @param callable|EventListener $listener A callback function or EventListener instance to handle the event.
     * @return void
     *
     * @example
     * ```php
     * // Register with closure
     * $hub->register('user.login', function($user) {
     *     Log::info("User {$user->name} logged in");
     * });
     *
     * // Register with EventListener instance
     * $hub->register('user.created', new SendWelcomeEmailListener());
     * ```
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
     * Trigger an event, invoking all registered listeners.
     *
     * Events can be processed either synchronously (immediately) or asynchronously
     * (queued for background processing). Asynchronous events are serialized and
     * added to the queue for later execution.
     *
     * @param string $eventName The name of the event to trigger.
     * @param mixed $event The event data or object to pass to listeners. Must be serializable if async.
     * @param bool $async If true, the event is queued for asynchronous processing. Default is false.
     * @return void
     *
     * @example
     * ```php
     * // Synchronous event
     * $hub->trigger('user.updated', $user);
     *
     * // Asynchronous event (queued)
     * $hub->trigger('email.send', $emailData, async: true);
     * ```
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
     * Trigger an event synchronously.
     *
     * Executes all registered listeners for the given event immediately in the
     * order they were registered. If no listeners are registered for the event,
     * this method returns silently without error.
     *
     * @param string $eventName The name of the event to trigger.
     * @param mixed $event The event data or object to pass to each listener.
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
     * Check if an event has registered listeners.
     *
     * Useful for conditional logic to determine if triggering an event
     * will have any effect.
     *
     * @param string $eventName The name of the event to check.
     * @return bool True if at least one listener is registered, false otherwise.
     *
     * @example
     * ```php
     * if ($hub->hasListeners('user.deleted')) {
     *     $hub->trigger('user.deleted', $user);
     * }
     * ```
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Execute the initialization callback if one is registered.
     *
     * This method is called automatically during construction and invokes
     * the static initialization callback if it has been set via onInitialized().
     *
     * @return void
     * @see EventHub::onInitialized()
     */
    private function emitInitializationEvent(): void
    {
        if (self::$initializationCallback !== null) {
            (self::$initializationCallback)($this);
        }
    }

    /**
     * Set a callback to execute when EventHub is instantiated.
     *
     * This provides a hook for configuring event listeners before the EventHub
     * is used. The callback receives the EventHub instance as its only parameter,
     * allowing registration of listeners during initialization.
     *
     * This method should be called early in your application bootstrap, before
     * the EventHub is instantiated (typically in bootstrap.php or config files).
     *
     * @param \Closure $callback A closure that receives the EventHub instance: function(EventHub $hub): void
     * @return void
     *
     * @example
     * ```php
     * // In bootstrap.php or config/events.php
     * EventHub::onInitialized(function(EventHub $hub) {
     *     $hub->register('user.created', new SendWelcomeEmailListener());
     *     $hub->register('user.updated', new LogUserChangeListener());
     *     $hub->register('order.placed', function($order) {
     *         // Process order
     *     });
     * });
     * ```
     */
    public static function onInitialized(\Closure $callback): void
    {
        self::$initializationCallback = $callback;
    }
}
