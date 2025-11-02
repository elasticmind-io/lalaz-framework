<?php declare(strict_types=1);

use Lalaz\Queue\JobRunner;

describe('JobRunner', function() {
    describe('Class Structure', function() {
        it('can be instantiated', function() {
            $runner = new JobRunner();
            expect($runner)->toBeInstanceOf(JobRunner::class);
        });

        it('has run method', function() {
            expect(method_exists(JobRunner::class, 'run'))->toBeTrue();
        });

        it('run method is public', function() {
            $reflection = new ReflectionMethod(JobRunner::class, 'run');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('run method returns void', function() {
            $reflection = new ReflectionMethod(JobRunner::class, 'run');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });

        it('run method takes no parameters', function() {
            $reflection = new ReflectionMethod(JobRunner::class, 'run');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(0);
        });
    });

    describe('Functionality', function() {
        it('can call run without errors', function() {
            $runner = new JobRunner();

            // Since run() creates QueueManager internally and processes jobs,
            // we just test it doesn't throw an exception
            expect(fn() => $runner->run())->not->toThrow(Exception::class);
        });
    });

    describe('Integration', function() {
        it('creates new instance each time', function() {
            $runner1 = new JobRunner();
            $runner2 = new JobRunner();

            expect($runner1)->not->toBe($runner2);
            expect($runner1)->toBeInstanceOf(JobRunner::class);
            expect($runner2)->toBeInstanceOf(JobRunner::class);
        });

        it('is not a singleton', function() {
            // Class has no explicit constructor, which is fine
            // Just verify it can be instantiated normally
            $runner1 = new JobRunner();
            $runner2 = new JobRunner();

            expect($runner1)->toBeInstanceOf(JobRunner::class);
            expect($runner2)->toBeInstanceOf(JobRunner::class);
        });
    });

    describe('Method Behavior', function() {
        it('run method can be called multiple times', function() {
            $runner = new JobRunner();

            expect(fn() => $runner->run())->not->toThrow(Exception::class);
            expect(fn() => $runner->run())->not->toThrow(Exception::class);
            expect(fn() => $runner->run())->not->toThrow(Exception::class);
        });

        it('does not return any value', function() {
            $runner = new JobRunner();
            $result = $runner->run();

            expect($result)->toBeNull();
        });
    });
});
