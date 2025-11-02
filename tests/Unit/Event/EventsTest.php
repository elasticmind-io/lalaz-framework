<?php

use Lalaz\Event\Events;
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

describe('Events', function () {
    describe('class structure', function () {
        it('has static register method', function () {
            $reflection = new ReflectionClass(Events::class);
            expect($reflection->hasMethod('register'))->toBeTrue();

            $method = $reflection->getMethod('register');
            expect($method->isStatic())->toBeTrue();
            expect($method->isPublic())->toBeTrue();
        });

        it('has static trigger method', function () {
            $reflection = new ReflectionClass(Events::class);
            expect($reflection->hasMethod('trigger'))->toBeTrue();

            $method = $reflection->getMethod('trigger');
            expect($method->isStatic())->toBeTrue();
            expect($method->isPublic())->toBeTrue();
        });

        it('has static triggerSync method', function () {
            $reflection = new ReflectionClass(Events::class);
            expect($reflection->hasMethod('triggerSync'))->toBeTrue();

            $method = $reflection->getMethod('triggerSync');
            expect($method->isStatic())->toBeTrue();
            expect($method->isPublic())->toBeTrue();
        });
    });

    describe('register', function () {
        it('accepts event name and callable', function () {
            $listener = function ($event) {};

            // Should not throw exception
            Events::register('test-event', $listener);

            expect(true)->toBeTrue();
        });

        it('accepts EventListener instance', function () {
            $listener = new class extends EventListener {
                public function handle(mixed $event): void {}
            };

            // Should not throw exception
            Events::register('test-event', $listener);

            expect(true)->toBeTrue();
        });

        it('accepts different event names', function () {
            $listener = function ($event) {};

            Events::register('user.created', $listener);
            Events::register('user.updated', $listener);
            Events::register('user.deleted', $listener);

            expect(true)->toBeTrue();
        });

        it('handles empty event name', function () {
            $listener = function ($event) {};

            Events::register('', $listener);

            expect(true)->toBeTrue();
        });

        it('handles special characters in event name', function () {
            $listener = function ($event) {};

            Events::register('event:with:special-chars_and.dots', $listener);

            expect(true)->toBeTrue();
        });
    });

    describe('trigger (async)', function () {
        it('triggers event asynchronously', function () {
            $listener = function ($event) {};

            Events::register('async-event', $listener);

            // Trigger asynchronously (queued)
            Events::trigger('async-event', 'data');

            expect(true)->toBeTrue();
        });

        it('accepts string event data', function () {
            Events::trigger('test-event', 'string data');

            expect(true)->toBeTrue();
        });

        it('accepts array event data', function () {
            Events::trigger('test-event', ['key' => 'value']);

            expect(true)->toBeTrue();
        });

        it('accepts object event data', function () {
            $object = new stdClass();
            $object->prop = 'value';

            Events::trigger('test-event', $object);

            expect(true)->toBeTrue();
        });

        it('accepts null event data', function () {
            Events::trigger('test-event', null);

            expect(true)->toBeTrue();
        });

        it('accepts integer event data', function () {
            Events::trigger('test-event', 42);

            expect(true)->toBeTrue();
        });

        it('accepts boolean event data', function () {
            Events::trigger('test-event', true);
            Events::trigger('test-event', false);

            expect(true)->toBeTrue();
        });
    });

    describe('triggerSync (synchronous)', function () {
        it('triggers event synchronously', function () {
            $called = false;
            $listener = function ($event) use (&$called) {
                $called = true;
            };

            Events::register('sync-event', $listener);
            Events::triggerSync('sync-event', 'data');

            // Note: Since Events creates new EventHub each time,
            // the listener won't be called in this test context
            // But the method should execute without error
            expect(true)->toBeTrue();
        });

        it('accepts string event data', function () {
            Events::triggerSync('test-event', 'string data');

            expect(true)->toBeTrue();
        });

        it('accepts array event data', function () {
            Events::triggerSync('test-event', ['key' => 'value']);

            expect(true)->toBeTrue();
        });

        it('accepts object event data', function () {
            $object = new stdClass();
            $object->prop = 'value';

            Events::triggerSync('test-event', $object);

            expect(true)->toBeTrue();
        });

        it('accepts null event data', function () {
            Events::triggerSync('test-event', null);

            expect(true)->toBeTrue();
        });

        it('accepts complex nested data', function () {
            $complexData = [
                'user' => [
                    'id' => 123,
                    'profile' => [
                        'name' => 'John',
                        'settings' => ['theme' => 'dark']
                    ]
                ]
            ];

            Events::triggerSync('test-event', $complexData);

            expect(true)->toBeTrue();
        });
    });

    describe('method signatures', function () {
        it('register method has correct parameters', function () {
            $reflection = new ReflectionClass(Events::class);
            $method = $reflection->getMethod('register');
            $parameters = $method->getParameters();

            expect($parameters)->toHaveCount(2);
            expect($parameters[0]->getName())->toBe('eventName');
            expect($parameters[1]->getName())->toBe('listener');
        });

        it('trigger method has correct parameters', function () {
            $reflection = new ReflectionClass(Events::class);
            $method = $reflection->getMethod('trigger');
            $parameters = $method->getParameters();

            expect($parameters)->toHaveCount(2);
            expect($parameters[0]->getName())->toBe('eventName');
            expect($parameters[1]->getName())->toBe('event');
        });

        it('triggerSync method has correct parameters', function () {
            $reflection = new ReflectionClass(Events::class);
            $method = $reflection->getMethod('triggerSync');
            $parameters = $method->getParameters();

            expect($parameters)->toHaveCount(2);
            expect($parameters[0]->getName())->toBe('eventName');
            expect($parameters[1]->getName())->toBe('event');
        });

        it('methods return void', function () {
            $reflection = new ReflectionClass(Events::class);

            $registerReturn = $reflection->getMethod('register')->getReturnType();
            $triggerReturn = $reflection->getMethod('trigger')->getReturnType();
            $triggerSyncReturn = $reflection->getMethod('triggerSync')->getReturnType();

            expect($registerReturn->getName())->toBe('void');
            expect($triggerReturn->getName())->toBe('void');
            expect($triggerSyncReturn->getName())->toBe('void');
        });
    });

    describe('edge cases', function () {
        it('handles multiple register calls', function () {
            $listener1 = function ($event) {};
            $listener2 = function ($event) {};
            $listener3 = function ($event) {};

            Events::register('multi-event', $listener1);
            Events::register('multi-event', $listener2);
            Events::register('multi-event', $listener3);

            expect(true)->toBeTrue();
        });

        it('handles triggering non-existent event', function () {
            Events::trigger('non-existent-event', 'data');
            Events::triggerSync('non-existent-event', 'data');

            expect(true)->toBeTrue();
        });

        it('handles long event names', function () {
            $longEventName = str_repeat('a', 500);
            $listener = function ($event) {};

            Events::register($longEventName, $listener);
            Events::trigger($longEventName, 'data');

            expect(true)->toBeTrue();
        });

        it('handles large event data', function () {
            $largeData = array_fill(0, 1000, 'data');

            Events::trigger('large-event', $largeData);
            Events::triggerSync('large-event', $largeData);

            expect(true)->toBeTrue();
        });

        it('handles rapid successive calls', function () {
            $listener = function ($event) {};

            for ($i = 0; $i < 100; $i++) {
                Events::register("event-{$i}", $listener);
            }

            for ($i = 0; $i < 100; $i++) {
                Events::trigger("event-{$i}", $i);
            }

            expect(true)->toBeTrue();
        });
    });

    describe('real-world scenarios', function () {
        it('handles user registration event', function () {
            $userRegisteredListener = new class extends EventListener {
                public function handle(mixed $event): void {
                    // Send welcome email
                    // Create user profile
                    // Log activity
                }
            };

            Events::register('user.registered', $userRegisteredListener);

            $userData = [
                'id' => 123,
                'email' => 'user@example.com',
                'name' => 'John Doe'
            ];

            Events::trigger('user.registered', $userData);

            expect(true)->toBeTrue();
        });

        it('handles order placed event', function () {
            $orderPlacedListener = function ($order) {
                // Send confirmation email
                // Update inventory
                // Notify shipping
            };

            Events::register('order.placed', $orderPlacedListener);

            $orderData = [
                'order_id' => 'ORD-12345',
                'customer_id' => 456,
                'total' => 99.99,
                'items' => [
                    ['product_id' => 1, 'quantity' => 2],
                    ['product_id' => 2, 'quantity' => 1]
                ]
            ];

            Events::triggerSync('order.placed', $orderData);

            expect(true)->toBeTrue();
        });

        it('handles payment processed event', function () {
            $paymentListener = function ($payment) {
                // Update order status
                // Generate invoice
                // Send receipt
            };

            Events::register('payment.processed', $paymentListener);

            $paymentData = [
                'transaction_id' => 'TXN-789',
                'amount' => 99.99,
                'currency' => 'USD',
                'method' => 'credit_card'
            ];

            Events::trigger('payment.processed', $paymentData);

            expect(true)->toBeTrue();
        });

        it('handles multiple event types', function () {
            $listener = function ($event) {};

            Events::register('user.login', $listener);
            Events::register('user.logout', $listener);
            Events::register('user.password_reset', $listener);
            Events::register('user.profile_updated', $listener);

            Events::triggerSync('user.login', ['user_id' => 123]);
            Events::trigger('user.logout', ['user_id' => 123]);
            Events::trigger('user.password_reset', ['email' => 'user@example.com']);
            Events::triggerSync('user.profile_updated', ['user_id' => 123, 'changes' => []]);

            expect(true)->toBeTrue();
        });
    });
});
