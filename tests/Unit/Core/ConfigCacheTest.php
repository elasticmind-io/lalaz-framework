<?php

declare(strict_types=1);

use Lalaz\Core\Config;

beforeEach(function () {
    // Clear any existing cache
    Config::clearCache();

    // Set up test cache file
    $this->cacheFile = __DIR__ . '/../../storage/test_config_cache.php';
    $this->envFile = __DIR__ . '/../../storage/test.env';

    // Clean up any existing test files
    if (file_exists($this->cacheFile)) {
        unlink($this->cacheFile);
    }
    if (file_exists($this->envFile)) {
        unlink($this->envFile);
    }

    // Create test directory
    $dir = dirname($this->cacheFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
});

afterEach(function () {
    // Clean up test files
    if (file_exists($this->cacheFile)) {
        unlink($this->cacheFile);
    }
    if (file_exists($this->envFile)) {
        unlink($this->envFile);
    }

    // Clear cache
    Config::clearCache();
});test('config cache is disabled by default', function () {
    expect(Config::isCacheEnabled())->toBeFalse();
});

test('can set cache file path', function () {
    Config::setCacheFile($this->cacheFile);
    expect(true)->toBeTrue(); // No exception thrown
});

test('loadFromCache returns false when cache does not exist', function () {
    Config::setCacheFile($this->cacheFile);
    Config::set('CONFIG_CACHE_ENABLED', true);

    expect(Config::loadFromCache())->toBeFalse();
});

test('loadFromCache returns false when cache is disabled', function () {
    Config::setCacheFile($this->cacheFile);
    Config::set('CONFIG_CACHE_ENABLED', false);

    expect(Config::loadFromCache())->toBeFalse();
});

test('can save config to cache', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_DEBUG=true\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);

    expect(Config::saveToCache($this->envFile))->toBeTrue()
        ->and(file_exists($this->cacheFile))->toBeTrue();
});

test('cached file contains expected structure', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=TestApp\nAPP_ENV=testing\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    $cached = require $this->cacheFile;

    expect($cached)->toBeArray()
        ->and($cached)->toHaveKey('config')
        ->and($cached)->toHaveKey('hash')
        ->and($cached)->toHaveKey('timestamp')
        ->and($cached['config'])->toBeArray();
});

test('cached config preserves values correctly', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=TestApp\nAPP_ENV=production\nAPP_DEBUG=false\nAPP_PORT=8080\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    $cached = require $this->cacheFile;

    expect($cached['config']['APP_NAME'])->toBe('TestApp')
        ->and($cached['config']['APP_ENV'])->toBe('production')
        ->and($cached['config']['APP_DEBUG'])->toBe('false')
        ->and($cached['config']['APP_PORT'])->toBe('8080');
});

test('can load config from cache', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=CachedApp\nAPP_ENV=production\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    // Clear in-memory cache
    Config::clearCache();

    // Enable cache and load from cache
    Config::set('CONFIG_CACHE_ENABLED', true);
    Config::setCacheFile($this->cacheFile);

    expect(Config::loadFromCache())->toBeTrue()
        ->and(Config::get('APP_NAME'))->toBe('CachedApp')
        ->and(Config::get('APP_ENV'))->toBe('production');
});

test('cache hash changes when env file changes', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=TestApp\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    $cached1 = require $this->cacheFile;
    $hash1 = $cached1['hash'];

    // Modify .env file
    sleep(1); // Ensure file modification time changes
    file_put_contents($this->envFile, "APP_NAME=ModifiedApp\n");

    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    $cached2 = require $this->cacheFile;
    $hash2 = $cached2['hash'];

    expect($hash1)->not->toBe($hash2);
});

test('clearConfigCache removes cache file', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=TestApp\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    expect(file_exists($this->cacheFile))->toBeTrue();

    expect(Config::clearConfigCache())->toBeTrue()
        ->and(file_exists($this->cacheFile))->toBeFalse();
});

test('clearConfigCache returns false when no cache exists', function () {
    Config::setCacheFile($this->cacheFile);

    expect(Config::clearConfigCache())->toBeFalse();
});

test('saveToCache creates cache directory if not exists', function () {
    $nestedCacheFile = __DIR__ . '/../../storage/nested/deep/config.php';

    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=TestApp\n");

    Config::setCacheFile($nestedCacheFile);
    Config::load($this->envFile, '=', true);

    expect(Config::saveToCache($this->envFile))->toBeTrue()
        ->and(file_exists($nestedCacheFile))->toBeTrue();

    // Cleanup
    unlink($nestedCacheFile);
    rmdir(dirname($nestedCacheFile));
    rmdir(dirname(dirname($nestedCacheFile)));
});

test('saveToCache returns false when cache file is not set', function () {
    file_put_contents($this->envFile, "APP_NAME=TestApp\n");

    Config::load($this->envFile, '=', true);

    expect(Config::saveToCache($this->envFile))->toBeFalse();
});

test('loadFromCache validates cache structure', function () {
    // Create invalid cache file
    file_put_contents($this->cacheFile, "<?php\nreturn ['invalid' => 'structure'];\n");

    Config::setCacheFile($this->cacheFile);
    Config::set('CONFIG_CACHE_ENABLED', true);

    expect(Config::loadFromCache())->toBeFalse();
});

test('cache file has readable format', function () {
    file_put_contents($this->envFile, "APP_NAME=TestApp\nAPP_ENV=testing\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    $contents = file_get_contents($this->cacheFile);

    expect($contents)->toContain('<?php')
        ->and($contents)->toContain('Config cache generated at')
        ->and($contents)->toContain('return array');
});

test('config load uses cache when enabled', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=FromEnv\nAPP_ENV=development\n");

    // First load and cache
    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    // Modify .env file
    file_put_contents($this->envFile, "APP_NAME=ModifiedEnv\nAPP_ENV=production\n");

    // Clear in-memory cache
    Config::clearCache();

    // Enable cache and load again
    Config::set('CONFIG_CACHE_ENABLED', true);
    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile);

    // Should load from cache, not from modified .env
    expect(Config::get('APP_NAME'))->toBe('FromEnv')
        ->and(Config::get('APP_ENV'))->toBe('development');
});

test('config load bypasses cache when disabled', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_NAME=FromEnv\n");

    // First load and cache
    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    // Modify .env file
    file_put_contents($this->envFile, "APP_NAME=ModifiedEnv\n");

    // Clear in-memory cache
    Config::clearCache();

    // Load again with cache disabled
    Config::set('CONFIG_CACHE_ENABLED', false);
    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);

    // Should load from .env, not from cache
    expect(Config::get('APP_NAME'))->toBe('ModifiedEnv');
});

test('cached config preserves type information for getTyped', function () {
    // Create test .env file
    file_put_contents($this->envFile, "APP_DEBUG=true\nAPP_PORT=8080\nAPP_VERSION=1.5\n");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    // Clear and reload from cache
    Config::clearCache();
    Config::set('CONFIG_CACHE_ENABLED', true);
    Config::setCacheFile($this->cacheFile);
    Config::loadFromCache();

    expect(Config::getTyped('APP_DEBUG', false, 'bool'))->toBeTrue()
        ->and(Config::getTyped('APP_PORT', 0, 'int'))->toBe(8080)
        ->and(Config::getTyped('APP_VERSION', 0.0, 'float'))->toBe(1.5);
});

test('cache handles empty env file gracefully', function () {
    // Create empty .env file
    file_put_contents($this->envFile, "");

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);

    expect(Config::saveToCache($this->envFile))->toBeTrue();

    $cached = require $this->cacheFile;
    expect($cached['config'])->toBeArray();
});

test('cache handles comments and empty lines in env file', function () {
    // Create .env file with comments
    $envContent = <<<ENV
# Application settings
APP_NAME=TestApp

# Environment
APP_ENV=production
; This is also a comment
APP_DEBUG=false
ENV;

    file_put_contents($this->envFile, $envContent);

    Config::setCacheFile($this->cacheFile);
    Config::load($this->envFile, '=', true);
    Config::saveToCache($this->envFile);

    $cached = require $this->cacheFile;

    expect($cached['config']['APP_NAME'])->toBe('TestApp')
        ->and($cached['config']['APP_ENV'])->toBe('production')
        ->and($cached['config']['APP_DEBUG'])->toBe('false');
});
