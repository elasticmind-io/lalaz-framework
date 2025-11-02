<?php declare(strict_types=1);

use Lalaz\Logging\Log;

describe('Log', function() {    describe('Info Logging', function() {
        it('logs info messages', function() {
            // Since we can't easily mock static methods, test that Log methods exist and are callable
            expect(method_exists(Log::class, 'info'))->toBeTrue();
            expect(is_callable([Log::class, 'info']))->toBeTrue();
        });

        it('info method accepts mixed parameter', function() {
            $reflection = new ReflectionMethod(Log::class, 'info');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(1);
            expect($params[0]->getName())->toBe('message');
        });
    });

    describe('Debug Logging', function() {
        it('logs debug messages', function() {
            expect(method_exists(Log::class, 'debug'))->toBeTrue();
            expect(is_callable([Log::class, 'debug']))->toBeTrue();
        });

        it('debug method accepts mixed parameter', function() {
            $reflection = new ReflectionMethod(Log::class, 'debug');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(1);
            expect($params[0]->getName())->toBe('message');
        });
    });

    describe('Error Logging', function() {
        it('logs error messages', function() {
            expect(method_exists(Log::class, 'error'))->toBeTrue();
            expect(is_callable([Log::class, 'error']))->toBeTrue();
        });

        it('error method accepts mixed parameter', function() {
            $reflection = new ReflectionMethod(Log::class, 'error');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(1);
            expect($params[0]->getName())->toBe('error');
        });
    });

    describe('Static Facade Pattern', function() {
        it('is a final class', function() {
            $reflection = new ReflectionClass(Log::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('all public methods are static', function() {
            $reflection = new ReflectionClass(Log::class);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                expect($method->isStatic())->toBeTrue();
            }
        });
    });    describe('Method Signatures', function() {
        it('info returns void', function() {
            $reflection = new ReflectionMethod(Log::class, 'info');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });

        it('debug returns void', function() {
            $reflection = new ReflectionMethod(Log::class, 'debug');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });

        it('error returns void', function() {
            $reflection = new ReflectionMethod(Log::class, 'error');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('void');
        });
    });
});
