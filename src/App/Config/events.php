<?php declare(strict_types=1);

use Lalaz\Event\EventHub;
use App\Events\UserRegisteredEvent;

function onEventHubInitialized(EventHub $eventHub): void
{
    $eventHub->register('hello', function ($e) {
        // handle your event here
    });
}
