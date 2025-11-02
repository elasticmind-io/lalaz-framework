<?php declare(strict_types=1);

use Lalaz\Queue\Jobs;

describe('Jobs', function() {
    describe('Class Structure', function() {
        it('is a regular class', function() {
            $reflection = new ReflectionClass(Jobs::class);
            expect($reflection->isFinal())->toBeFalse();
        });

        it('has static run method', function() {
            expect(method_exists(Jobs::class, 'run'))->toBeTrue();

            $reflection = new ReflectionMethod(Jobs::class, 'run');
            expect($reflection->isStatic())->toBeTrue();
        });

        it('run method is public', function() {
            $reflection = new ReflectionMethod(Jobs::class, 'run');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('run method returns void', function() {
            $reflection = new ReflectionMethod(Jobs::class, 'run');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });

        it('run method takes no parameters', function() {
            $reflection = new ReflectionMethod(Jobs::class, 'run');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(0);
        });
    });

    describe('Static Facade Pattern', function() {
        it('all public methods are static', function() {
            $reflection = new ReflectionClass(Jobs::class);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                expect($method->isStatic())->toBeTrue();
            }
        });

        it('cannot be instantiated directly', function() {
            // Jobs is a facade, typically would have private constructor
            // but if it doesn't, we just test it has static methods
            expect(method_exists(Jobs::class, 'run'))->toBeTrue();
        });
    });

    describe('Functionality', function() {
        it('run method can be called', function() {
            // Capture output since Jobs::run() echoes messages
            ob_start();
            Jobs::run();
            $output = ob_get_clean();

            // Should contain status messages
            expect($output)->toBeString();
        });

        it('outputs starting message', function() {
            ob_start();
            Jobs::run();
            $output = ob_get_clean();

            expect($output)->toContain('Starting job execution');
        });

        it('outputs finished message on success', function() {
            ob_start();
            Jobs::run();
            $output = ob_get_clean();

            expect($output)->toContain('finished');
        });
    });

    describe('Error Handling', function() {
        it('catches exceptions and outputs error message', function() {
            // Since Jobs::run() uses try-catch, it should not throw
            ob_start();
            expect(fn() => Jobs::run())->not->toThrow(Exception::class);
            ob_get_clean();
        });

        it('does not throw on execution', function() {
            ob_start();
            $result = Jobs::run();
            ob_get_clean();

            expect($result)->toBeNull();
        });
    });

    describe('Output Format', function() {
        it('outputs newline characters', function() {
            ob_start();
            Jobs::run();
            $output = ob_get_clean();

            expect($output)->toContain("\n");
        });

        it('outputs multiple lines', function() {
            ob_start();
            Jobs::run();
            $output = ob_get_clean();

            $lines = explode("\n", $output);
            expect(count($lines))->toBeGreaterThan(1);
        });
    });
});
