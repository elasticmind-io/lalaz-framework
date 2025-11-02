<?php

use Lalaz\Event\EventListener;

describe('EventListener', function () {
    describe('abstract class structure', function () {
        it('is an abstract class', function () {
            $reflection = new ReflectionClass(EventListener::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

        it('has handle method', function () {
            $reflection = new ReflectionClass(EventListener::class);
            expect($reflection->hasMethod('handle'))->toBeTrue();
        });

        it('handle method is abstract', function () {
            $reflection = new ReflectionClass(EventListener::class);
            $method = $reflection->getMethod('handle');
            expect($method->isAbstract())->toBeTrue();
        });

        it('handle method is public', function () {
            $reflection = new ReflectionClass(EventListener::class);
            $method = $reflection->getMethod('handle');
            expect($method->isPublic())->toBeTrue();
        });

        it('handle method accepts mixed event parameter', function () {
            $reflection = new ReflectionClass(EventListener::class);
            $method = $reflection->getMethod('handle');
            $parameters = $method->getParameters();

            expect($parameters)->toHaveCount(1);
            expect($parameters[0]->getName())->toBe('event');
        });

        it('handle method returns void', function () {
            $reflection = new ReflectionClass(EventListener::class);
            $method = $reflection->getMethod('handle');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });
    });

    describe('implementation', function () {
        it('can be extended by concrete classes', function () {
            $listener = new class extends EventListener {
                public $handled = false;
                public $receivedEvent = null;

                public function handle(mixed $event): void
                {
                    $this->handled = true;
                    $this->receivedEvent = $event;
                }
            };

            expect($listener)->toBeInstanceOf(EventListener::class);
        });

        it('concrete implementation can handle events', function () {
            $listener = new class extends EventListener {
                public $handled = false;
                public $receivedEvent = null;

                public function handle(mixed $event): void
                {
                    $this->handled = true;
                    $this->receivedEvent = $event;
                }
            };

            $listener->handle('test-event');

            expect($listener->handled)->toBeTrue();
            expect($listener->receivedEvent)->toBe('test-event');
        });

        it('handle method can accept different data types', function () {
            $listener = new class extends EventListener {
                public $receivedEvents = [];

                public function handle(mixed $event): void
                {
                    $this->receivedEvents[] = $event;
                }
            };

            $listener->handle('string');
            $listener->handle(123);
            $listener->handle(['key' => 'value']);
            $listener->handle((object)['prop' => 'value']);

            expect($listener->receivedEvents)->toHaveCount(4);
            expect($listener->receivedEvents[0])->toBe('string');
            expect($listener->receivedEvents[1])->toBe(123);
            expect($listener->receivedEvents[2])->toBe(['key' => 'value']);
        });
    });

    describe('edge cases', function () {
        it('can handle null events', function () {
            $listener = new class extends EventListener {
                public $receivedEvent = 'not-null';

                public function handle(mixed $event): void
                {
                    $this->receivedEvent = $event;
                }
            };

            $listener->handle(null);

            expect($listener->receivedEvent)->toBeNull();
        });

        it('can be used with multiple event types', function () {
            $listener = new class extends EventListener {
                public $events = [];

                public function handle(mixed $event): void
                {
                    $this->events[] = $event;
                }
            };

            $listener->handle('event1');
            $listener->handle('event2');
            $listener->handle('event3');

            expect($listener->events)->toHaveCount(3);
        });
    });
});
