<?php

use Lalaz\Storage\Contracts\StorageInterface;

describe('StorageInterface', function () {
    describe('interface structure', function () {
        it('is an interface', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            expect($reflection->isInterface())->toBeTrue();
        });

        it('has upload method', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            expect($reflection->hasMethod('upload'))->toBeTrue();
        });

        it('has download method', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            expect($reflection->hasMethod('download'))->toBeTrue();
        });

        it('has delete method', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            expect($reflection->hasMethod('delete'))->toBeTrue();
        });

        it('has getPublicUrl method', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            expect($reflection->hasMethod('getPublicUrl'))->toBeTrue();
        });
    });

    describe('upload method signature', function () {
        it('accepts path and localPath parameters', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('upload');
            $params = $method->getParameters();

            expect($params)->toHaveCount(2);
            expect($params[0]->getName())->toBe('path');
            expect($params[1]->getName())->toBe('localPath');
        });

        it('returns string', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('upload');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('string');
        });

        it('path parameter is string type', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('upload');
            $params = $method->getParameters();
            $pathType = $params[0]->getType();

            expect($pathType)->not->toBeNull();
            expect($pathType->getName())->toBe('string');
        });

        it('localPath parameter is string type', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('upload');
            $params = $method->getParameters();
            $localPathType = $params[1]->getType();

            expect($localPathType)->not->toBeNull();
            expect($localPathType->getName())->toBe('string');
        });
    });

    describe('download method signature', function () {
        it('accepts path parameter', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('download');
            $params = $method->getParameters();

            expect($params)->toHaveCount(1);
            expect($params[0]->getName())->toBe('path');
        });

        it('path parameter is string type', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('download');
            $params = $method->getParameters();
            $pathType = $params[0]->getType();

            expect($pathType)->not->toBeNull();
            expect($pathType->getName())->toBe('string');
        });

        it('returns string', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('download');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('string');
        });
    });

    describe('delete method signature', function () {
        it('accepts path parameter', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('delete');
            $params = $method->getParameters();

            expect($params)->toHaveCount(1);
            expect($params[0]->getName())->toBe('path');
        });

        it('path parameter is string type', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('delete');
            $params = $method->getParameters();
            $pathType = $params[0]->getType();

            expect($pathType)->not->toBeNull();
            expect($pathType->getName())->toBe('string');
        });

        it('returns bool', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('delete');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('bool');
        });
    });

    describe('getPublicUrl method signature', function () {
        it('accepts path parameter', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('getPublicUrl');
            $params = $method->getParameters();

            expect($params)->toHaveCount(1);
            expect($params[0]->getName())->toBe('path');
        });

        it('path parameter is string type', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('getPublicUrl');
            $params = $method->getParameters();
            $pathType = $params[0]->getType();

            expect($pathType)->not->toBeNull();
            expect($pathType->getName())->toBe('string');
        });

        it('returns string', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $method = $reflection->getMethod('getPublicUrl');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('string');
        });
    });

    describe('method visibility', function () {
        it('all methods are public', function () {
            $reflection = new ReflectionClass(StorageInterface::class);
            $methods = $reflection->getMethods();

            foreach ($methods as $method) {
                expect($method->isPublic())->toBeTrue();
            }
        });
    });
});
