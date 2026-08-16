<?php

use Lalaz\Core\Config;
use Lalaz\Data\Adapters\ConnectionAdapterResolver;
use Lalaz\Data\Adapters\DbLessAdapter;
use Lalaz\Data\Adapters\MysqlAdapter;
use Lalaz\Data\Adapters\PostgresAdapter;
use Lalaz\Data\Adapters\SQLiteAdapter;

describe('ConnectionAdapterResolver', function () {
    beforeEach(function () {
        Config::clearCache();
        $_ENV = array_diff_key($_ENV, array_flip([
            'DB_PROVIDER',
            'SQLITE_PATH',
            'DB_HOST',
            'DB_PORT',
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'DB_SCHEMA',
        ]));
    });

    it('resolves sqlite adapter when DB_PROVIDER=sqlite', function () {
        $path = sys_get_temp_dir() . '/lalaz_sqlite_' . uniqid() . '.sqlite';

        Config::set('DB_PROVIDER', 'sqlite');
        Config::set('SQLITE_PATH', $path);

        $adapter = ConnectionAdapterResolver::resolve();

        expect($adapter)->toBeInstanceOf(SQLiteAdapter::class);

        if (file_exists($path)) {
            unlink($path);
        }
    });

    it('resolves mysql adapter when DB_PROVIDER=mysql', function () {
        Config::set('DB_PROVIDER', 'mysql');
        Config::set('DB_HOST', 'localhost');
        Config::set('DB_PORT', '3306');
        Config::set('DB_NAME', 'test');
        Config::set('DB_USER', 'root');
        Config::set('DB_PASSWORD', 'secret');

        $adapter = ConnectionAdapterResolver::resolve();

        expect($adapter)->toBeInstanceOf(MysqlAdapter::class);
    });

    it('resolves postgres adapter when DB_PROVIDER=pgsql', function () {
        Config::set('DB_PROVIDER', 'pgsql');
        Config::set('DB_HOST', 'localhost');
        Config::set('DB_PORT', '5432');
        Config::set('DB_NAME', 'test');
        Config::set('DB_USER', 'postgres');
        Config::set('DB_PASSWORD', 'secret');
        Config::set('DB_SCHEMA', 'custom');

        $adapter = ConnectionAdapterResolver::resolve();

        expect($adapter)->toBeInstanceOf(PostgresAdapter::class);
    });

    it('resolves dbless adapter when DB_PROVIDER=dbless', function () {
        Config::set('DB_PROVIDER', 'dbless');

        $adapter = ConnectionAdapterResolver::resolve();

        expect($adapter)->toBeInstanceOf(DbLessAdapter::class);
    });

    it('throws for unsupported provider', function () {
        Config::set('DB_PROVIDER', 'oracle');

        expect(fn () => ConnectionAdapterResolver::resolve())
            ->toThrow(RuntimeException::class);
    });

    it('throws when sqlite configuration is missing path', function () {
        Config::set('DB_PROVIDER', 'sqlite');

        expect(fn () => ConnectionAdapterResolver::resolve())
            ->toThrow(RuntimeException::class, 'SQLITE_PATH is required when DB_PROVIDER=sqlite');
    });

    it('throws when mysql configuration is incomplete', function () {
        Config::set('DB_PROVIDER', 'mysql');
        Config::set('DB_HOST', 'localhost');
        Config::set('DB_PORT', '3306');
        Config::set('DB_NAME', 'test');
        Config::set('DB_USER', 'root');
        // Deliberately omit DB_PASSWORD to trigger validation

        expect(fn () => ConnectionAdapterResolver::resolve())
            ->toThrow(RuntimeException::class, 'Incomplete MySQL database configuration.');
    });

    it('throws when postgres configuration is incomplete', function () {
        Config::set('DB_PROVIDER', 'pgsql');
        Config::set('DB_HOST', 'localhost');
        Config::set('DB_PORT', '5432');
        Config::set('DB_NAME', 'test');
        Config::set('DB_PASSWORD', 'secret');
        // Missing DB_USER should surface validation error

        expect(fn () => ConnectionAdapterResolver::resolve())
            ->toThrow(RuntimeException::class, 'Incomplete PostgreSQL database configuration.');
    });
});
