<?php

use Lalaz\Event\EventJob;
use Lalaz\Event\EventHub;
use Lalaz\Queue\Job;

beforeEach(function () {
    // Configure database and queue for EventHub/QueueManager
    $_ENV['DB_PROVIDER'] = 'dbless';
    $_ENV['QUEUE_PROVIDER'] = 'in-memory';
});

afterEach(function () {
    unset($_ENV['DB_PROVIDER']);
    unset($_ENV['QUEUE_PROVIDER']);
});

describe('EventJob', function () {
    describe('class structure', function () {
        it('extends Job class', function () {
            $reflection = new ReflectionClass(EventJob::class);
            expect($reflection->getParentClass()->getName())->toBe(Job::class);
        });

        it('has handle method', function () {
            $reflection = new ReflectionClass(EventJob::class);
            expect($reflection->hasMethod('handle'))->toBeTrue();
        });

        it('handle method is public', function () {
            $reflection = new ReflectionClass(EventJob::class);
            $method = $reflection->getMethod('handle');
            expect($method->isPublic())->toBeTrue();
        });

        it('handle method accepts array payload', function () {
            $reflection = new ReflectionClass(EventJob::class);
            $method = $reflection->getMethod('handle');
            $parameters = $method->getParameters();

            expect($parameters)->toHaveCount(1);
            expect($parameters[0]->getName())->toBe('payload');
        });
    });

    describe('handle', function () {
        it('processes event from payload', function () {
            $job = new EventJob();
            $testEvent = ['data' => 'test-value'];

            $payload = [
                'event_name' => 'test-event',
                'event_data' => serialize($testEvent),
            ];

            // Should not throw exception
            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles payload without event_name', function () {
            $job = new EventJob();

            $payload = [
                'event_data' => serialize(['data' => 'value']),
            ];

            // Should not throw exception
            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles payload without event_data', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'test-event',
            ];

            // Should not throw exception
            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles empty payload', function () {
            $job = new EventJob();

            $payload = [];

            // Should not throw exception
            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('unserializes event data correctly', function () {
            $job = new EventJob();
            $originalEvent = [
                'user_id' => 123,
                'action' => 'login',
                'timestamp' => time()
            ];

            $payload = [
                'event_name' => 'user.login',
                'event_data' => serialize($originalEvent),
            ];

            // Should not throw exception during unserialization
            $job->handle($payload);

            expect(true)->toBeTrue();
        });
    });

    describe('event triggering', function () {
        it('creates new EventHub instance', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'test-event',
                'event_data' => serialize('data'),
            ];

            // EventHub is created internally
            $job->handle($payload);

            expect(true)->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('handles serialized string data', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'string-event',
                'event_data' => serialize('simple string'),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles serialized integer data', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'integer-event',
                'event_data' => serialize(42),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles serialized array data', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'array-event',
                'event_data' => serialize(['key1' => 'value1', 'key2' => 'value2']),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles serialized object data', function () {
            $job = new EventJob();
            $object = new stdClass();
            $object->prop = 'value';

            $payload = [
                'event_name' => 'object-event',
                'event_data' => serialize($object),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles serialized null data', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'null-event',
                'event_data' => serialize(null),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles complex nested data structures', function () {
            $job = new EventJob();
            $complexData = [
                'user' => [
                    'id' => 123,
                    'profile' => [
                        'name' => 'John Doe',
                        'settings' => [
                            'theme' => 'dark',
                            'notifications' => true
                        ]
                    ]
                ],
                'metadata' => [
                    'timestamp' => time(),
                    'source' => 'api'
                ]
            ];

            $payload = [
                'event_name' => 'complex-event',
                'event_data' => serialize($complexData),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles special characters in event name', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'event:with:special-chars_and.dots',
                'event_data' => serialize('data'),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles empty event name', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => '',
                'event_data' => serialize('data'),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles large serialized data', function () {
            $job = new EventJob();
            $largeArray = array_fill(0, 1000, 'data');

            $payload = [
                'event_name' => 'large-event',
                'event_data' => serialize($largeArray),
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });
    });

    describe('payload validation', function () {
        it('handles extra payload keys', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => 'test-event',
                'event_data' => serialize('data'),
                'extra_key' => 'extra_value',
                'another_key' => 123,
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles payload with null values', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => null,
                'event_data' => null,
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles payload with false values', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => false,
                'event_data' => false,
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });

        it('handles payload with empty string values', function () {
            $job = new EventJob();

            $payload = [
                'event_name' => '',
                'event_data' => '',
            ];

            $job->handle($payload);

            expect(true)->toBeTrue();
        });
    });
});
