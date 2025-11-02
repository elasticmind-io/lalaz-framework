<?php declare(strict_types=1);

use Lalaz\Queue\QueueManager;
use Lalaz\Queue\Contracts\QueueProviderInterface;

describe('QueueManager', function() {
    describe('Constructor', function() {
        it('can be instantiated', function() {
            $manager = new QueueManager();
            expect($manager)->toBeInstanceOf(QueueManager::class);
        });

        it('creates provider based on configuration', function() {
            // QueueManager should create a provider internally
            $manager = new QueueManager();
            expect($manager)->toBeInstanceOf(QueueManager::class);
        });
    });

    describe('Method Signatures', function() {
        it('has addJob method', function() {
            expect(method_exists(QueueManager::class, 'addJob'))->toBeTrue();
        });

        it('addJob accepts jobClass and payload', function() {
            $reflection = new ReflectionMethod(QueueManager::class, 'addJob');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(2);
            expect($params[0]->getName())->toBe('jobClass');
            expect($params[1]->getName())->toBe('payload');
        });

        it('addJob returns bool', function() {
            $reflection = new ReflectionMethod(QueueManager::class, 'addJob');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('bool');
        });

        it('has processJobs method', function() {
            expect(method_exists(QueueManager::class, 'processJobs'))->toBeTrue();
        });

        it('processJobs returns void', function() {
            $reflection = new ReflectionMethod(QueueManager::class, 'processJobs');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });

        it('processJobs takes no parameters', function() {
            $reflection = new ReflectionMethod(QueueManager::class, 'processJobs');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(0);
        });
    });

    describe('addJob Method', function() {
        it('can call addJob without errors', function() {
            $manager = new QueueManager();

            expect(fn() => $manager->addJob('TestJob', []))->not->toThrow(Exception::class);
        });

        it('accepts empty payload', function() {
            $manager = new QueueManager();
            $result = $manager->addJob('TestJob', []);

            expect($result)->toBeBool();
        });

        it('accepts payload with data', function() {
            $manager = new QueueManager();
            $payload = ['user_id' => 123, 'action' => 'send_email'];
            $result = $manager->addJob('TestJob', $payload);

            expect($result)->toBeBool();
        });

        it('accepts complex payload', function() {
            $manager = new QueueManager();
            $payload = [
                'user' => [
                    'id' => 42,
                    'email' => 'test@example.com',
                ],
                'metadata' => [
                    'timestamp' => time(),
                    'source' => 'api',
                ],
            ];
            $result = $manager->addJob('ComplexJob', $payload);

            expect($result)->toBeBool();
        });
    });

    describe('processJobs Method', function() {
        it('can call processJobs without errors', function() {
            $manager = new QueueManager();

            expect(fn() => $manager->processJobs())->not->toThrow(Exception::class);
        });

        it('processJobs returns null (void)', function() {
            $manager = new QueueManager();
            $result = $manager->processJobs();

            expect($result)->toBeNull();
        });
    });

    describe('Provider Integration', function() {
        it('uses a provider internally', function() {
            $manager = new QueueManager();

            // The manager should have a provider property
            $reflection = new ReflectionClass(QueueManager::class);
            $properties = $reflection->getProperties();

            $hasProvider = false;
            foreach ($properties as $property) {
                if ($property->getName() === 'provider') {
                    $hasProvider = true;
                    break;
                }
            }

            expect($hasProvider)->toBeTrue();
        });

        it('provider implements QueueProviderInterface', function() {
            $manager = new QueueManager();

            $reflection = new ReflectionClass(QueueManager::class);
            $property = $reflection->getProperty('provider');
            $property->setAccessible(true);
            $provider = $property->getValue($manager);

            expect($provider)->toBeInstanceOf(QueueProviderInterface::class);
        });
    });

    describe('Multiple Operations', function() {
        it('can add multiple jobs', function() {
            $manager = new QueueManager();

            $result1 = $manager->addJob('Job1', ['id' => 1]);
            $result2 = $manager->addJob('Job2', ['id' => 2]);
            $result3 = $manager->addJob('Job3', ['id' => 3]);

            expect($result1)->toBeBool();
            expect($result2)->toBeBool();
            expect($result3)->toBeBool();
        });

        it('can call processJobs multiple times', function() {
            $manager = new QueueManager();

            expect(fn() => $manager->processJobs())->not->toThrow(Exception::class);
            expect(fn() => $manager->processJobs())->not->toThrow(Exception::class);
        });

        it('can add jobs and process them', function() {
            $manager = new QueueManager();

            $manager->addJob('TestJob', ['test' => 'data']);
            expect(fn() => $manager->processJobs())->not->toThrow(Exception::class);
        });
    });

    describe('Edge Cases', function() {
        it('handles job class with namespace', function() {
            $manager = new QueueManager();
            $result = $manager->addJob('App\\Jobs\\SendEmailJob', []);

            expect($result)->toBeBool();
        });

        it('handles special characters in job class name', function() {
            $manager = new QueueManager();
            $result = $manager->addJob('Test_Job_123', []);

            expect($result)->toBeBool();
        });

        it('handles numeric values in payload', function() {
            $manager = new QueueManager();
            $payload = ['count' => 100, 'price' => 99.99];
            $result = $manager->addJob('TestJob', $payload);

            expect($result)->toBeBool();
        });

        it('handles boolean values in payload', function() {
            $manager = new QueueManager();
            $payload = ['active' => true, 'deleted' => false];
            $result = $manager->addJob('TestJob', $payload);

            expect($result)->toBeBool();
        });

        it('handles null values in payload', function() {
            $manager = new QueueManager();
            $payload = ['optional' => null];
            $result = $manager->addJob('TestJob', $payload);

            expect($result)->toBeBool();
        });

        it('handles unicode in payload', function() {
            $manager = new QueueManager();
            $payload = ['message' => 'Unicode: 你好 🚀'];
            $result = $manager->addJob('TestJob', $payload);

            expect($result)->toBeBool();
        });
    });

    describe('Instance Independence', function() {
        it('different instances work independently', function() {
            $manager1 = new QueueManager();
            $manager2 = new QueueManager();

            expect($manager1)->not->toBe($manager2);
            expect($manager1)->toBeInstanceOf(QueueManager::class);
            expect($manager2)->toBeInstanceOf(QueueManager::class);
        });
    });
});
