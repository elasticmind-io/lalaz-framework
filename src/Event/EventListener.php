<?php declare(strict_types=1);

namespace Lalaz\Event;

/**
 * Class Listener
 *
 * A base class for all event listeners. Provides a standard method
 * signature for handling events.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
abstract class EventListener
{
    /**
     * Handle the event.
     *
     * Every listener must implement this method to handle the event.
     *
     * @param mixed $event The event object or data.
     * @return void
     */
    abstract public function handle(mixed $event): void;
}
