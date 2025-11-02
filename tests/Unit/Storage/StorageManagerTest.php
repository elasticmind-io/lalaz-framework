<?php

use Lalaz\Storage\StorageManager;
use Lalaz\Storage\Contracts\StorageInterface;
use Lalaz\Storage\Adapters\LocalStorageAdapter;

describe('StorageManager', function () {
    beforeEach(function () {
        // Set default storage configuration
        $_ENV['STORAGE_DRIVER'] = LocalStorageAdapter::class;
        $_ENV['STORAGE_CONFIG'] = ['path' => sys_get_temp_dir()];
    });

    afterEach(function () {
        unset($_ENV['STORAGE_DRIVER']);
        unset($_ENV['STORAGE_CONFIG']);
    });

    describe('constructor', function () {
        it('creates instance successfully', function () {
            $manager = new StorageManager();
            expect($manager)->toBeInstanceOf(StorageManager::class);
        });

        it('initializes storage driver from config', function () {
            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect($driver)->toBeInstanceOf(StorageInterface::class);
        });

        it('uses LocalStorageAdapter as default when no driver configured', function () {
            unset($_ENV['STORAGE_DRIVER']);

            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
        });

        it('uses configured driver class', function () {
            $_ENV['STORAGE_DRIVER'] = LocalStorageAdapter::class;

            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
        });
    });

    describe('getDriver()', function () {
        it('returns StorageInterface instance', function () {
            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect($driver)->toBeInstanceOf(StorageInterface::class);
        });

        it('returns same driver instance on multiple calls', function () {
            $manager = new StorageManager();
            $driver1 = $manager->getDriver();
            $driver2 = $manager->getDriver();

            expect($driver1)->toBe($driver2);
        });

        it('has upload method on driver', function () {
            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect(method_exists($driver, 'upload'))->toBeTrue();
        });

        it('has download method on driver', function () {
            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect(method_exists($driver, 'download'))->toBeTrue();
        });

        it('has delete method on driver', function () {
            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect(method_exists($driver, 'delete'))->toBeTrue();
        });

        it('has getPublicUrl method on driver', function () {
            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect(method_exists($driver, 'getPublicUrl'))->toBeTrue();
        });
    });

    describe('configuration handling', function () {
        it('passes config to driver constructor', function () {
            $_ENV['STORAGE_CONFIG'] = ['path' => '/custom/path'];

            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
        });

        it('handles empty config', function () {
            $_ENV['STORAGE_CONFIG'] = null;

            // LocalStorageAdapter requires path, so this will throw
            expect(fn() => new StorageManager())
                ->toThrow(TypeError::class);
        });        it('handles null driver with default fallback', function () {
            $_ENV['STORAGE_DRIVER'] = null;

            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
        });
    });

    describe('method visibility', function () {
        it('getDriver is public', function () {
            $reflection = new ReflectionClass(StorageManager::class);
            $method = $reflection->getMethod('getDriver');

            expect($method->isPublic())->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('different instances create independent drivers', function () {
            $manager1 = new StorageManager();
            $manager2 = new StorageManager();

            $driver1 = $manager1->getDriver();
            $driver2 = $manager2->getDriver();

            // Different manager instances, different driver instances
            expect($driver1)->not->toBe($driver2);
        });

        it('handles config as array directly', function () {
            // Test that config() helper returns array
            $_ENV['STORAGE_CONFIG'] = ['path' => sys_get_temp_dir()];

            $manager = new StorageManager();
            expect($manager)->toBeInstanceOf(StorageManager::class);
        });
    });

    describe('real-world scenarios', function () {
        it('creates manager for local storage', function () {
            $_ENV['STORAGE_DRIVER'] = LocalStorageAdapter::class;
            $_ENV['STORAGE_CONFIG'] = ['path' => '/var/www/storage'];

            $manager = new StorageManager();
            $driver = $manager->getDriver();

            expect($driver)->toBeInstanceOf(LocalStorageAdapter::class);
            expect($driver)->toBeInstanceOf(StorageInterface::class);
        });        it('can be used to get driver and perform operations', function () {
            $manager = new StorageManager();
            $driver = $manager->getDriver();

            // Verify driver has all required methods
            expect(method_exists($driver, 'upload'))->toBeTrue();
            expect(method_exists($driver, 'download'))->toBeTrue();
            expect(method_exists($driver, 'delete'))->toBeTrue();
            expect(method_exists($driver, 'getPublicUrl'))->toBeTrue();
        });

        it('initializes once and reuses driver', function () {
            $manager = new StorageManager();

            // Multiple calls to getDriver
            $driver1 = $manager->getDriver();
            $driver2 = $manager->getDriver();
            $driver3 = $manager->getDriver();

            expect($driver1)->toBe($driver2);
            expect($driver2)->toBe($driver3);
        });
    });
});
