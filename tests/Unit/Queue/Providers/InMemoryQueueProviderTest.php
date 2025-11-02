<?php declare(strict_types=1);

use Lalaz\Queue\Providers\InMemoryQueueProvider;
use Lalaz\Queue\Contracts\QueueProviderInterface;
use Lalaz\Queue\Job;

// Test job for InMemoryQueueProvider
class InMemoryTestJob extends Job
{
    public static int $executionCount = 0;
    public static array $lastPayload = [];

    public function handle(array $payload): void
    {
        self::$executionCount++;
        self::$lastPayload = $payload;
    }

    public static function reset(): void
    {
        self::$executionCount = 0;
        self::$lastPayload = [];
    }
}

// Failing test job
class FailingJob extends Job
{
    public function handle(array $payload): void
    {
        throw new Exception('Job failed intentionally');
    }
}

describe('InMemoryQueueProvider', function() {
    beforeEach(function() {
        InMemoryTestJob::reset();
        $this->provider = new InMemoryQueueProvider();
    });

    describe('Interface Implementation', function() {
        it('implements QueueProviderInterface', function() {
            expect($this->provider)->toBeInstanceOf(QueueProviderInterface::class);
        });

        it('has add method', function() {
            expect(method_exists($this->provider, 'add'))->toBeTrue();
        });

        it('has process method', function() {
            expect(method_exists($this->provider, 'process'))->toBeTrue();
        });

        it('has all method for inspection', function() {
            expect(method_exists($this->provider, 'all'))->toBeTrue();
        });
    });

    describe('Adding Jobs', function() {
        it('adds job to queue successfully', function() {
            $result = $this->provider->add(InMemoryTestJob::class, ['test' => 'data']);
            expect($result)->toBeTrue();
        });

        it('adds job with empty payload', function() {
            $result = $this->provider->add(InMemoryTestJob::class, []);
            expect($result)->toBeTrue();
        });

        it('adds job with complex payload', function() {
            $payload = [
                'user_id' => 123,
                'email' => 'test@example.com',
                'metadata' => ['key' => 'value'],
            ];
            $result = $this->provider->add(InMemoryTestJob::class, $payload);
            expect($result)->toBeTrue();
        });

        it('stores job in queue', function() {
            $this->provider->add(InMemoryTestJob::class, ['test' => 'data']);
            $queue = $this->provider->all();

            expect($queue)->toBeArray();
            expect(count($queue))->toBe(1);
        });

        it('stores job with correct structure', function() {
            $this->provider->add(InMemoryTestJob::class, ['test' => 'data']);
            $queue = $this->provider->all();

            expect($queue[0])->toHaveKey('task');
            expect($queue[0])->toHaveKey('payload');
            expect($queue[0])->toHaveKey('status');
        });

        it('sets initial status as pending', function() {
            $this->provider->add(InMemoryTestJob::class, ['test' => 'data']);
            $queue = $this->provider->all();

            expect($queue[0]['status'])->toBe('pending');
        });

        it('stores correct job class', function() {
            $this->provider->add(InMemoryTestJob::class, ['test' => 'data']);
            $queue = $this->provider->all();

            expect($queue[0]['task'])->toBe(InMemoryTestJob::class);
        });

        it('stores correct payload', function() {
            $payload = ['user_id' => 42, 'action' => 'send'];
            $this->provider->add(InMemoryTestJob::class, $payload);
            $queue = $this->provider->all();

            expect($queue[0]['payload'])->toBe($payload);
        });

        it('can add multiple jobs', function() {
            $this->provider->add(InMemoryTestJob::class, ['id' => 1]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 2]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 3]);

            $queue = $this->provider->all();
            expect(count($queue))->toBe(3);
        });
    });

    describe('Processing Jobs', function() {
        it('processes pending job', function() {
            $this->provider->add(InMemoryTestJob::class, ['test' => 'value']);
            $this->provider->process();

            expect(InMemoryTestJob::$executionCount)->toBe(1);
            expect(InMemoryTestJob::$lastPayload)->toBe(['test' => 'value']);
        });

        it('updates job status to processing', function() {
            $this->provider->add(InMemoryTestJob::class, ['test' => 'data']);
            $this->provider->process();

            $queue = $this->provider->all();
            expect($queue[0]['status'])->toBe('completed');
        });

        it('marks job as completed after successful execution', function() {
            $this->provider->add(InMemoryTestJob::class, ['test' => 'data']);
            $this->provider->process();

            $queue = $this->provider->all();
            expect($queue[0]['status'])->toBe('completed');
        });

        it('processes only first pending job', function() {
            $this->provider->add(InMemoryTestJob::class, ['id' => 1]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 2]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 3]);

            $this->provider->process();

            // Should process only one job
            expect(InMemoryTestJob::$executionCount)->toBe(1);
            expect(InMemoryTestJob::$lastPayload)->toBe(['id' => 1]);
        });

        it('can process jobs sequentially', function() {
            $this->provider->add(InMemoryTestJob::class, ['id' => 1]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 2]);

            $this->provider->process();
            expect(InMemoryTestJob::$lastPayload)->toBe(['id' => 1]);

            $this->provider->process();
            expect(InMemoryTestJob::$lastPayload)->toBe(['id' => 2]);
        });

        it('skips already processed jobs', function() {
            $this->provider->add(InMemoryTestJob::class, ['id' => 1]);
            $this->provider->process();

            InMemoryTestJob::reset();

            $this->provider->process();
            // Should not execute again
            expect(InMemoryTestJob::$executionCount)->toBe(0);
        });

        it('does nothing when queue is empty', function() {
            $this->provider->process();
            expect(InMemoryTestJob::$executionCount)->toBe(0);
        });
    });

    describe('Error Handling', function() {
        it('marks job as failed when exception occurs', function() {
            $this->provider->add(FailingJob::class, ['test' => 'data']);
            $this->provider->process();

            $queue = $this->provider->all();
            expect($queue[0]['status'])->toBe('failed');
        });

        it('marks job as failed when class does not exist', function() {
            $this->provider->add('NonExistentJob', ['test' => 'data']);
            $this->provider->process();

            $queue = $this->provider->all();
            expect($queue[0]['status'])->toBe('failed');
        });

        it('continues after failed job', function() {
            $this->provider->add(FailingJob::class, ['id' => 1]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 2]);

            $this->provider->process();
            $this->provider->process();

            expect(InMemoryTestJob::$executionCount)->toBe(1);
            expect(InMemoryTestJob::$lastPayload)->toBe(['id' => 2]);
        });
    });

    describe('Queue Inspection', function() {
        it('returns empty array for new provider', function() {
            $queue = $this->provider->all();
            expect($queue)->toBeArray();
            expect(count($queue))->toBe(0);
        });

        it('returns all jobs in queue', function() {
            $this->provider->add(InMemoryTestJob::class, ['id' => 1]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 2]);

            $queue = $this->provider->all();
            expect(count($queue))->toBe(2);
        });

        it('preserves job order', function() {
            $this->provider->add(InMemoryTestJob::class, ['id' => 1]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 2]);
            $this->provider->add(InMemoryTestJob::class, ['id' => 3]);

            $queue = $this->provider->all();
            expect($queue[0]['payload']['id'])->toBe(1);
            expect($queue[1]['payload']['id'])->toBe(2);
            expect($queue[2]['payload']['id'])->toBe(3);
        });
    });

    describe('Edge Cases', function() {
        it('handles job with numeric values', function() {
            $payload = ['count' => 100, 'price' => 99.99];
            $this->provider->add(InMemoryTestJob::class, $payload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['count'])->toBe(100);
            expect(InMemoryTestJob::$lastPayload['price'])->toBe(99.99);
        });

        it('handles job with boolean values', function() {
            $payload = ['active' => true, 'deleted' => false];
            $this->provider->add(InMemoryTestJob::class, $payload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['active'])->toBeTrue();
            expect(InMemoryTestJob::$lastPayload['deleted'])->toBeFalse();
        });

        it('handles job with null values', function() {
            $payload = ['optional' => null];
            $this->provider->add(InMemoryTestJob::class, $payload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['optional'])->toBeNull();
        });

        it('handles job with nested arrays', function() {
            $payload = [
                'user' => [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
            ];
            $this->provider->add(InMemoryTestJob::class, $payload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['user']['name'])->toBe('John');
        });

        it('handles job with unicode characters', function() {
            $payload = ['message' => 'Unicode: 你好 🚀'];
            $this->provider->add(InMemoryTestJob::class, $payload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['message'])->toContain('你好');
        });

        it('handles job with special characters', function() {
            $payload = ['text' => 'Special: @#$%^&*()'];
            $this->provider->add(InMemoryTestJob::class, $payload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['text'])->toContain('@#$%^&*()');
        });
    });

    describe('Real-world Scenarios', function() {
        it('processes email sending job', function() {
            $emailPayload = [
                'to' => 'user@example.com',
                'subject' => 'Welcome',
                'template' => 'welcome_email',
                'data' => ['name' => 'John Doe'],
            ];

            $this->provider->add(InMemoryTestJob::class, $emailPayload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['to'])->toBe('user@example.com');
            expect(InMemoryTestJob::$lastPayload['subject'])->toBe('Welcome');
        });

        it('processes notification job', function() {
            $notificationPayload = [
                'user_id' => 123,
                'type' => 'push',
                'message' => 'You have a new message',
                'priority' => 'high',
            ];

            $this->provider->add(InMemoryTestJob::class, $notificationPayload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['user_id'])->toBe(123);
            expect(InMemoryTestJob::$lastPayload['priority'])->toBe('high');
        });

        it('processes data export job', function() {
            $exportPayload = [
                'format' => 'csv',
                'filters' => ['status' => 'active', 'created_after' => '2024-01-01'],
                'email_to' => 'admin@example.com',
            ];

            $this->provider->add(InMemoryTestJob::class, $exportPayload);
            $this->provider->process();

            expect(InMemoryTestJob::$lastPayload['format'])->toBe('csv');
            expect(InMemoryTestJob::$lastPayload['filters'])->toHaveKey('status');
        });
    });
});
