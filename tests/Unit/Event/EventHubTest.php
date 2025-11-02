<?php

use Lalaz\Event\EventHub;
use Lalaz\Event\EventListener;

beforeEach(function () {
    // Configure database and queue for EventHub/QueueManager
    $_ENV['DB_PROVIDER'] = 'dbless';
    $_ENV['QUEUE_PROVIDER'] = 'in-memory';
});

afterEach(function () {
    unset($_ENV['DB_PROVIDER']);
    unset($_ENV['QUEUE_PROVIDER']);
});

describe('EventHub', function () {
    describe('instantiation', function () {
        it('can be instantiated', function () {
            $eventHub = new EventHub();
            expect($eventHub)->toBeInstanceOf(EventHub::class);
        });

        it('initializes with empty listeners', function () {
            $eventHub = new EventHub();
            expect($eventHub->hasListeners('any-event'))->toBeFalse();
        });
    });

    describe('register', function () {
        it('registers a callable listener', function () {
            $eventHub = new EventHub();
            $listener = function ($event) {};

            $eventHub->register('test-event', $listener);

            expect($eventHub->hasListeners('test-event'))->toBeTrue();
        });

        it('registers multiple listeners for same event', function () {
            $eventHub = new EventHub();
            $listener1 = function ($event) {};
            $listener2 = function ($event) {};

            $eventHub->register('test-event', $listener1);
            $eventHub->register('test-event', $listener2);

            expect($eventHub->hasListeners('test-event'))->toBeTrue();
        });

        it('registers listeners for different events', function () {
            $eventHub = new EventHub();
            $listener1 = function ($event) {};
            $listener2 = function ($event) {};

            $eventHub->register('event-1', $listener1);
            $eventHub->register('event-2', $listener2);

            expect($eventHub->hasListeners('event-1'))->toBeTrue();
            expect($eventHub->hasListeners('event-2'))->toBeTrue();
        });

        it('registers EventListener instance', function () {
            $eventHub = new EventHub();
            $listener = new class extends EventListener {
                public function handle(mixed $event): void {}
            };

            $eventHub->register('test-event', $listener);

            expect($eventHub->hasListeners('test-event'))->toBeTrue();
        });

        it('handles empty event names', function () {
            $eventHub = new EventHub();
            $listener = function ($event) {};

            $eventHub->register('', $listener);

            expect($eventHub->hasListeners(''))->toBeTrue();
        });
    });

    describe('triggerSync', function () {
        it('triggers registered listener', function () {
            $eventHub = new EventHub();
            $called = false;

            $eventHub->register('test-event', function ($event) use (&$called) {
                $called = true;
            });

            $eventHub->triggerSync('test-event', 'event-data');

            expect($called)->toBeTrue();
        });

        it('passes event data to listener', function () {
            $eventHub = new EventHub();
            $receivedData = null;

            $eventHub->register('test-event', function ($event) use (&$receivedData) {
                $receivedData = $event;
            });

            $eventHub->triggerSync('test-event', 'test-data');

            expect($receivedData)->toBe('test-data');
        });

        it('triggers all registered listeners', function () {
            $eventHub = new EventHub();
            $callCount = 0;

            $eventHub->register('test-event', function ($event) use (&$callCount) {
                $callCount++;
            });
            $eventHub->register('test-event', function ($event) use (&$callCount) {
                $callCount++;
            });
            $eventHub->register('test-event', function ($event) use (&$callCount) {
                $callCount++;
            });

            $eventHub->triggerSync('test-event', null);

            expect($callCount)->toBe(3);
        });

        it('does not trigger listeners of different events', function () {
            $eventHub = new EventHub();
            $called = false;

            $eventHub->register('other-event', function ($event) use (&$called) {
                $called = true;
            });

            $eventHub->triggerSync('test-event', null);

            expect($called)->toBeFalse();
        });

        it('does nothing when no listeners registered', function () {
            $eventHub = new EventHub();

            // Should not throw exception
            $eventHub->triggerSync('non-existent-event', 'data');

            expect(true)->toBeTrue();
        });

        it('triggers EventListener instances', function () {
            $eventHub = new EventHub();
            $listener = new class extends EventListener {
                public $called = false;
                public $receivedEvent = null;

                public function handle(mixed $event): void
                {
                    $this->called = true;
                    $this->receivedEvent = $event;
                }
            };

            $eventHub->register('test-event', $listener);
            $eventHub->triggerSync('test-event', 'data');

            expect($listener->called)->toBeTrue();
            expect($listener->receivedEvent)->toBe('data');
        });

        it('passes different data types to listeners', function () {
            $eventHub = new EventHub();
            $receivedEvents = [];

            $eventHub->register('test-event', function ($event) use (&$receivedEvents) {
                $receivedEvents[] = $event;
            });

            $eventHub->triggerSync('test-event', 'string');
            $eventHub->triggerSync('test-event', 123);
            $eventHub->triggerSync('test-event', ['array']);
            $eventHub->triggerSync('test-event', (object)['prop' => 'value']);

            expect($receivedEvents)->toHaveCount(4);
            expect($receivedEvents[0])->toBe('string');
            expect($receivedEvents[1])->toBe(123);
        });
    });

    describe('trigger', function () {
        it('triggers synchronously when async is false', function () {
            $eventHub = new EventHub();
            $called = false;

            $eventHub->register('test-event', function ($event) use (&$called) {
                $called = true;
            });

            $eventHub->trigger('test-event', 'data', false);

            expect($called)->toBeTrue();
        });

        it('queues event when async is true', function () {
            $eventHub = new EventHub();
            $called = false;

            $eventHub->register('test-event', function ($event) use (&$called) {
                $called = true;
            });

            $eventHub->trigger('test-event', 'data', true);

            // Event is queued, not immediately executed
            expect($called)->toBeFalse();
        });
    });

    describe('hasListeners', function () {
        it('returns true when listeners are registered', function () {
            $eventHub = new EventHub();
            $eventHub->register('test-event', function ($event) {});

            expect($eventHub->hasListeners('test-event'))->toBeTrue();
        });

        it('returns false when no listeners are registered', function () {
            $eventHub = new EventHub();

            expect($eventHub->hasListeners('test-event'))->toBeFalse();
        });

        it('returns false after registering different event', function () {
            $eventHub = new EventHub();
            $eventHub->register('other-event', function ($event) {});

            expect($eventHub->hasListeners('test-event'))->toBeFalse();
        });

        it('returns true when multiple listeners registered', function () {
            $eventHub = new EventHub();
            $eventHub->register('test-event', function ($event) {});
            $eventHub->register('test-event', function ($event) {});

            expect($eventHub->hasListeners('test-event'))->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('handles special characters in event names', function () {
            $eventHub = new EventHub();
            $called = false;

            $eventHub->register('event:with:colons', function ($event) use (&$called) {
                $called = true;
            });

            $eventHub->triggerSync('event:with:colons', null);

            expect($called)->toBeTrue();
        });

        it('handles events with null data', function () {
            $eventHub = new EventHub();
            $receivedData = 'not-null';

            $eventHub->register('test-event', function ($event) use (&$receivedData) {
                $receivedData = $event;
            });

            $eventHub->triggerSync('test-event', null);

            expect($receivedData)->toBeNull();
        });

        it('handles large event names', function () {
            $eventHub = new EventHub();
            $longEventName = str_repeat('a', 1000);
            $called = false;

            $eventHub->register($longEventName, function ($event) use (&$called) {
                $called = true;
            });

            $eventHub->triggerSync($longEventName, null);

            expect($called)->toBeTrue();
        });

        it('handles many listeners for single event', function () {
            $eventHub = new EventHub();
            $callCount = 0;

            for ($i = 0; $i < 100; $i++) {
                $eventHub->register('test-event', function ($event) use (&$callCount) {
                    $callCount++;
                });
            }

            $eventHub->triggerSync('test-event', null);

            expect($callCount)->toBe(100);
        });

        it('maintains listener execution order', function () {
            $eventHub = new EventHub();
            $order = [];

            $eventHub->register('test-event', function ($event) use (&$order) {
                $order[] = 'first';
            });
            $eventHub->register('test-event', function ($event) use (&$order) {
                $order[] = 'second';
            });
            $eventHub->register('test-event', function ($event) use (&$order) {
                $order[] = 'third';
            });

            $eventHub->triggerSync('test-event', null);

            expect($order)->toBe(['first', 'second', 'third']);
        });
    });

    describe('complex scenarios', function () {
        it('handles nested event triggering', function () {
            $eventHub = new EventHub();
            $nestedCalled = false;

            $eventHub->register('nested-event', function ($event) use (&$nestedCalled) {
                $nestedCalled = true;
            });

            $eventHub->register('main-event', function ($event) use ($eventHub) {
                $eventHub->triggerSync('nested-event', 'nested-data');
            });

            $eventHub->triggerSync('main-event', 'main-data');

            expect($nestedCalled)->toBeTrue();
        });

        it('handles event data modification in listeners', function () {
            $eventHub = new EventHub();
            $results = [];

            $eventHub->register('test-event', function ($event) use (&$results) {
                $results[] = $event['count'];
            });
            $eventHub->register('test-event', function ($event) use (&$results) {
                $results[] = $event['count'] * 2;
            });

            $eventHub->triggerSync('test-event', ['count' => 5]);

            expect($results)->toBe([5, 10]);
        });
    });
});
