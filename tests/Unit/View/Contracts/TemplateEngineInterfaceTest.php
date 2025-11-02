<?php

use Lalaz\View\Contracts\TemplateEngineInterface;

describe('TemplateEngineInterface', function () {
    describe('interface structure', function () {
        it('is an interface', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            expect($reflection->isInterface())->toBeTrue();
        });

        it('has render method', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            expect($reflection->hasMethod('render'))->toBeTrue();
        });
    });

    describe('render method signature', function () {
        it('accepts template and data parameters', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $method = $reflection->getMethod('render');
            $params = $method->getParameters();

            expect($params)->toHaveCount(2);
            expect($params[0]->getName())->toBe('template');
            expect($params[1]->getName())->toBe('data');
        });

        it('template parameter is string type', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $method = $reflection->getMethod('render');
            $params = $method->getParameters();
            $templateType = $params[0]->getType();

            expect($templateType)->not->toBeNull();
            expect($templateType->getName())->toBe('string');
        });

        it('data parameter is array type', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $method = $reflection->getMethod('render');
            $params = $method->getParameters();
            $dataType = $params[1]->getType();

            expect($dataType)->not->toBeNull();
            expect($dataType->getName())->toBe('array');
        });

        it('data parameter has default value', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $method = $reflection->getMethod('render');
            $params = $method->getParameters();

            expect($params[1]->isDefaultValueAvailable())->toBeTrue();
            expect($params[1]->getDefaultValue())->toBe([]);
        });

        it('returns string', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $method = $reflection->getMethod('render');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('string');
        });

        it('render method is public', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $method = $reflection->getMethod('render');

            expect($method->isPublic())->toBeTrue();
        });
    });

    describe('interface contract', function () {
        it('defines exactly one method', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $methods = $reflection->getMethods();

            expect($methods)->toHaveCount(1);
        });

        it('only method is render', function () {
            $reflection = new ReflectionClass(TemplateEngineInterface::class);
            $methods = $reflection->getMethods();

            expect($methods[0]->getName())->toBe('render');
        });
    });
});
