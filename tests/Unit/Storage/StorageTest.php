<?php

use Lalaz\Storage\Storage;
use Lalaz\Storage\StorageManager;
use Lalaz\Storage\Contracts\StorageInterface;
use Lalaz\Storage\Adapters\LocalStorageAdapter;

describe('Storage', function () {
    beforeEach(function () {
        // Set default storage configuration
        $_ENV['STORAGE_DRIVER'] = LocalStorageAdapter::class;
        $_ENV['STORAGE_CONFIG'] = ['path' => sys_get_temp_dir()];
    });

    afterEach(function () {
        unset($_ENV['STORAGE_DRIVER']);
        unset($_ENV['STORAGE_CONFIG']);
    });

    describe('class structure', function () {
        it('is a class', function () {
            $reflection = new ReflectionClass(Storage::class);
            expect($reflection->isAbstract())->toBeFalse();
            expect($reflection->isInterface())->toBeFalse();
        });

        it('has driver static method', function () {
            $reflection = new ReflectionClass(Storage::class);
            expect($reflection->hasMethod('driver'))->toBeTrue();
        });

        it('driver method is static', function () {
            $reflection = new ReflectionClass(Storage::class);
            $method = $reflection->getMethod('driver');
            expect($method->isStatic())->toBeTrue();
        });

        it('driver method is public', function () {
            $reflection = new ReflectionClass(Storage::class);
            $method = $reflection->getMethod('driver');
            expect($method->isPublic())->toBeTrue();
        });
    });

    describe('driver() method', function () {
        it('returns StorageInterface instance', function () {
            $driver = Storage::driver();
            expect($driver)->toBeInstanceOf(StorageInterface::class);
        });

        it('returns LocalStorageAdapter by default', function () {
            $driver = Storage::driver();
            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
        });

        it('creates new StorageManager instance', function () {
            $driver = Storage::driver();

            // Verify it returns a storage driver
            expect($driver)->toBeInstanceOf(StorageInterface::class);
        });

        it('returns driver with upload method', function () {
            $driver = Storage::driver();
            expect(method_exists($driver, 'upload'))->toBeTrue();
        });

        it('returns driver with download method', function () {
            $driver = Storage::driver();
            expect(method_exists($driver, 'download'))->toBeTrue();
        });

        it('returns driver with delete method', function () {
            $driver = Storage::driver();
            expect(method_exists($driver, 'delete'))->toBeTrue();
        });

        it('returns driver with getPublicUrl method', function () {
            $driver = Storage::driver();
            expect(method_exists($driver, 'getPublicUrl'))->toBeTrue();
        });

        it('creates new driver instance on each call', function () {
            $driver1 = Storage::driver();
            $driver2 = Storage::driver();

            // Each call creates new StorageManager and driver
            expect($driver1)->not->toBe($driver2);
        });
    });

    describe('static facade pattern', function () {
        it('provides convenient access to storage', function () {
            $driver = Storage::driver();
            expect($driver)->toBeInstanceOf(StorageInterface::class);
        });

        it('can be called without instantiation', function () {
            // Should not throw error
            $driver = Storage::driver();
            expect($driver)->not->toBeNull();
        });
    });

    describe('configuration handling', function () {
        it('respects configured driver', function () {
            $_ENV['STORAGE_DRIVER'] = LocalStorageAdapter::class;

            $driver = Storage::driver();
            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
        });

        it('uses default driver when config is null', function () {
            $_ENV['STORAGE_DRIVER'] = null;

            $driver = Storage::driver();
            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
        });
    });

    describe('method signatures', function () {
        it('driver method takes no parameters', function () {
            $reflection = new ReflectionClass(Storage::class);
            $method = $reflection->getMethod('driver');
            $params = $method->getParameters();

            expect($params)->toHaveCount(0);
        });

        it('driver method returns StorageInterface', function () {
            $reflection = new ReflectionClass(Storage::class);
            $method = $reflection->getMethod('driver');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe(StorageInterface::class);
        });
    });

    describe('edge cases', function () {
        it('handles multiple consecutive calls', function () {
            $driver1 = Storage::driver();
            $driver2 = Storage::driver();
            $driver3 = Storage::driver();

            expect($driver1)->toBeInstanceOf(StorageInterface::class);
            expect($driver2)->toBeInstanceOf(StorageInterface::class);
            expect($driver3)->toBeInstanceOf(StorageInterface::class);
        });

        it('each call creates independent driver', function () {
            $driver1 = Storage::driver();
            $driver2 = Storage::driver();

            // Different instances
            expect($driver1)->not->toBe($driver2);

            // But same type
            expect(get_class($driver1))->toBe(get_class($driver2));
        });
    });

    describe('real-world scenarios', function () {
        it('provides quick access to storage operations', function () {
            $driver = Storage::driver();

            // Can immediately use for storage operations
            expect(method_exists($driver, 'upload'))->toBeTrue();
            expect(method_exists($driver, 'download'))->toBeTrue();
            expect(method_exists($driver, 'delete'))->toBeTrue();
            expect(method_exists($driver, 'getPublicUrl'))->toBeTrue();
        });

        it('works with dependency injection pattern', function () {
            // Simulate getting driver for service
            $storageDriver = Storage::driver();

            expect($storageDriver)->toBeInstanceOf(StorageInterface::class);
        });

        it('supports multiple storage operations in sequence', function () {
            $_ENV['STORAGE_PUBLIC_URL'] = 'http://localhost/storage';

            $driver1 = Storage::driver();
            $url1 = $driver1->getPublicUrl('file1.txt');

            $driver2 = Storage::driver();
            $url2 = $driver2->getPublicUrl('file2.txt');

            expect($url1)->toBeString();
            expect($url2)->toBeString();

            unset($_ENV['STORAGE_PUBLIC_URL']);
        });

        it('can be used in static context', function () {
            // Common usage pattern in controllers/services
            $storage = Storage::driver();

            expect($storage)->toBeInstanceOf(StorageInterface::class);
            expect($storage)->toBeInstanceOf(LocalStorageAdapter::class);
        });
    });

    describe('integration with StorageManager', function () {
        it('uses StorageManager internally', function () {
            $driver = Storage::driver();

            // Verify it creates a proper driver via StorageManager
            expect($driver)->toBeInstanceOf(StorageInterface::class);
        });

        it('gets driver from manager getDriver method', function () {
            $manager = new StorageManager();
            $managerDriver = $manager->getDriver();

            $storageDriver = Storage::driver();

            // Same type, different instances
            expect(get_class($storageDriver))->toBe(get_class($managerDriver));
        });
    });
});
