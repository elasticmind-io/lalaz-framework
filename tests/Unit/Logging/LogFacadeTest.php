<?php

use Lalaz\Logging\Log;

/**
 * Tests for Log facade - verifies PSR-3 API surface is available.
 *
 * These tests validate the facade has all required PSR-3 methods without
 * actually invoking them (to avoid environment dependencies).
 */
describe('Log Facade API', function () {
    it('has all PSR-3 log level methods', function () {
        expect(method_exists(Log::class, 'emergency'))->toBeTrue();
        expect(method_exists(Log::class, 'alert'))->toBeTrue();
        expect(method_exists(Log::class, 'critical'))->toBeTrue();
        expect(method_exists(Log::class, 'error'))->toBeTrue();
        expect(method_exists(Log::class, 'warning'))->toBeTrue();
        expect(method_exists(Log::class, 'notice'))->toBeTrue();
        expect(method_exists(Log::class, 'info'))->toBeTrue();
        expect(method_exists(Log::class, 'debug'))->toBeTrue();
        expect(method_exists(Log::class, 'log'))->toBeTrue();
    });

    it('emergency method is callable', function () {
        expect(is_callable([Log::class, 'emergency']))->toBeTrue();
    });

    it('alert method is callable', function () {
        expect(is_callable([Log::class, 'alert']))->toBeTrue();
    });

    it('critical method is callable', function () {
        expect(is_callable([Log::class, 'critical']))->toBeTrue();
    });

    it('error method is callable', function () {
        expect(is_callable([Log::class, 'error']))->toBeTrue();
    });

    it('warning method is callable', function () {
        expect(is_callable([Log::class, 'warning']))->toBeTrue();
    });

    it('notice method is callable', function () {
        expect(is_callable([Log::class, 'notice']))->toBeTrue();
    });

    it('info method is callable', function () {
        expect(is_callable([Log::class, 'info']))->toBeTrue();
    });

    it('debug method is callable', function () {
        expect(is_callable([Log::class, 'debug']))->toBeTrue();
    });

    it('log method is callable', function () {
        expect(is_callable([Log::class, 'log']))->toBeTrue();
    });

    it('has correct method signatures via reflection', function () {
        $reflection = new ReflectionClass(Log::class);

        // Check emergency
        $emergency = $reflection->getMethod('emergency');
        expect($emergency->isPublic())->toBeTrue();
        expect($emergency->isStatic())->toBeTrue();
        expect($emergency->getNumberOfParameters())->toBe(2);
        expect($emergency->getNumberOfRequiredParameters())->toBe(1);

        // Check log
        $log = $reflection->getMethod('log');
        expect($log->isPublic())->toBeTrue();
        expect($log->isStatic())->toBeTrue();
        expect($log->getNumberOfParameters())->toBe(3);
        expect($log->getNumberOfRequiredParameters())->toBe(2);
    });

    it('emergency method accepts message and context parameters', function () {
        $reflection = new ReflectionClass(Log::class);
        $method = $reflection->getMethod('emergency');
        $params = $method->getParameters();

        expect(count($params))->toBe(2);
        expect($params[0]->getName())->toBe('message');
        expect($params[1]->getName())->toBe('context');
        expect($params[1]->isOptional())->toBeTrue();
    });

    it('log method accepts level, message and context parameters', function () {
        $reflection = new ReflectionClass(Log::class);
        $method = $reflection->getMethod('log');
        $params = $method->getParameters();

        expect(count($params))->toBe(3);
        expect($params[0]->getName())->toBe('level');
        expect($params[1]->getName())->toBe('message');
        expect($params[2]->getName())->toBe('context');
        expect($params[2]->isOptional())->toBeTrue();
    });

    it('all log level methods have consistent signatures', function () {
        $reflection = new ReflectionClass(Log::class);
        $methods = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $params = $method->getParameters();

            expect(count($params))->toBe(2, "$methodName should have 2 parameters");
            expect($params[0]->getName())->toBe('message', "$methodName first parameter should be 'message'");
            expect($params[1]->getName())->toBe('context', "$methodName second parameter should be 'context'");
            expect($params[1]->isOptional())->toBeTrue("$methodName context parameter should be optional");
        }
    });
});
