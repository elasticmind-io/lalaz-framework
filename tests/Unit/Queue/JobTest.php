<?php declare(strict_types=1);

use Lalaz\Queue\Job;
use Lalaz\Queue\Contracts\JobInterface;

// Stub for testing
class TestJob extends Job
{
    public static bool $handled = false;
    public static array $lastPayload = [];

    public function handle(array $payload): void
    {
        self::$handled = true;
        self::$lastPayload = $payload;
    }

    public static function reset(): void
    {
        self::$handled = false;
        self::$lastPayload = [];
    }
}

describe('Job', function() {
    beforeEach(function() {
        TestJob::reset();
    });

    describe('Interface Implementation', function() {
        it('implements JobInterface', function() {
            $job = new TestJob();
            expect($job)->toBeInstanceOf(JobInterface::class);
        });

        it('is an abstract class', function() {
            $reflection = new ReflectionClass(Job::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

        it('has handle method defined in interface', function() {
            expect(method_exists(TestJob::class, 'handle'))->toBeTrue();
        });
    });

    describe('performNow', function() {
        it('executes job immediately', function() {
            $job = new TestJob();
            $payload = ['task' => 'process', 'data' => 123];

            $job->performNow($payload);

            expect(TestJob::$handled)->toBeTrue();
            expect(TestJob::$lastPayload)->toBe($payload);
        });

        it('executes with empty payload', function() {
            $job = new TestJob();
            $job->performNow([]);

            expect(TestJob::$handled)->toBeTrue();
            expect(TestJob::$lastPayload)->toBe([]);
        });

        it('executes with complex payload', function() {
            $job = new TestJob();
            $payload = [
                'user_id' => 42,
                'action' => 'send_email',
                'metadata' => [
                    'email' => 'test@example.com',
                    'subject' => 'Welcome',
                    'template' => 'welcome',
                ],
                'priority' => 'high',
            ];

            $job->performNow($payload);

            expect(TestJob::$handled)->toBeTrue();
            expect(TestJob::$lastPayload)->toBe($payload);
        });

        it('calls handle method directly', function() {
            $job = new TestJob();
            $payload = ['test' => 'data'];

            // performNow should call handle
            $job->performNow($payload);

            expect(TestJob::$handled)->toBeTrue();
        });
    });

    describe('performLater', function() {
        it('has performLater method', function() {
            expect(method_exists(TestJob::class, 'performLater'))->toBeTrue();
        });

        it('performLater accepts array payload', function() {
            $reflection = new ReflectionMethod(TestJob::class, 'performLater');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(1);
            expect($params[0]->getName())->toBe('payload');
        });

        it('performLater returns void', function() {
            $reflection = new ReflectionMethod(TestJob::class, 'performLater');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });
    });

    describe('Method Signatures', function() {
        it('performNow returns void', function() {
            $reflection = new ReflectionMethod(TestJob::class, 'performNow');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });

        it('handle is abstract and must be implemented', function() {
            // handle() is defined in JobInterface, not in Job class
            // Job is abstract, so concrete classes must implement handle()
            $reflection = new ReflectionClass(Job::class);
            expect($reflection->isAbstract())->toBeTrue();
        });
    });

    describe('Edge Cases', function() {
        it('handles numeric values in payload', function() {
            $job = new TestJob();
            $payload = ['count' => 100, 'price' => 99.99];

            $job->performNow($payload);

            expect(TestJob::$lastPayload['count'])->toBe(100);
            expect(TestJob::$lastPayload['price'])->toBe(99.99);
        });

        it('handles boolean values in payload', function() {
            $job = new TestJob();
            $payload = ['active' => true, 'deleted' => false];

            $job->performNow($payload);

            expect(TestJob::$lastPayload['active'])->toBeTrue();
            expect(TestJob::$lastPayload['deleted'])->toBeFalse();
        });

        it('handles null values in payload', function() {
            $job = new TestJob();
            $payload = ['optional' => null];

            $job->performNow($payload);

            expect(TestJob::$lastPayload['optional'])->toBeNull();
        });

        it('handles nested arrays in payload', function() {
            $job = new TestJob();
            $payload = [
                'user' => [
                    'name' => 'John',
                    'address' => [
                        'city' => 'NYC',
                        'zip' => '10001',
                    ],
                ],
            ];

            $job->performNow($payload);

            expect(TestJob::$lastPayload['user']['name'])->toBe('John');
            expect(TestJob::$lastPayload['user']['address']['city'])->toBe('NYC');
        });

        it('handles special characters in payload', function() {
            $job = new TestJob();
            $payload = ['message' => 'Special: @#$%^&*()'];

            $job->performNow($payload);

            expect(TestJob::$lastPayload['message'])->toContain('@#$%^&*()');
        });

        it('handles unicode characters in payload', function() {
            $job = new TestJob();
            $payload = ['text' => 'Unicode: 你好 🚀'];

            $job->performNow($payload);

            expect(TestJob::$lastPayload['text'])->toContain('你好');
        });
    });

    describe('Multiple Executions', function() {
        it('can execute same job multiple times', function() {
            $job = new TestJob();

            $job->performNow(['run' => 1]);
            expect(TestJob::$lastPayload['run'])->toBe(1);

            $job->performNow(['run' => 2]);
            expect(TestJob::$lastPayload['run'])->toBe(2);

            $job->performNow(['run' => 3]);
            expect(TestJob::$lastPayload['run'])->toBe(3);
        });

        it('different job instances work independently', function() {
            $job1 = new TestJob();
            $job2 = new TestJob();

            expect($job1)->not->toBe($job2);
            expect($job1)->toBeInstanceOf(TestJob::class);
            expect($job2)->toBeInstanceOf(TestJob::class);
        });
    });
});
