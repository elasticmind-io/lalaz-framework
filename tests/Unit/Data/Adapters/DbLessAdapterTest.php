<?php

use Lalaz\Data\Adapters\DbLessAdapter;

describe('DbLessAdapter', function () {
    it('allows connect to be called without throwing', function () {
        $adapter = new DbLessAdapter();

        expect($adapter->connect())->toBeNull();
    });

    it('throws when executing query-related operations', function () {
        $adapter = new DbLessAdapter();

        expect(fn () => $adapter->query('SELECT 1'))
            ->toThrow(RuntimeException::class, 'Query not available: running in DB-less mode.');

        expect(fn () => $adapter->exec('DELETE FROM table'))
            ->toThrow(RuntimeException::class, 'Exec not available: running in DB-less mode.');

        expect(fn () => $adapter->prepare('SELECT 1'))
            ->toThrow(RuntimeException::class, 'Prepare not supported in DB-less mode.');
    });

    it('throws when using transaction controls', function () {
        $adapter = new DbLessAdapter();

        expect(fn () => $adapter->beginTransaction())
            ->toThrow(RuntimeException::class, 'Transactions are not supported in DB-less mode.');

        expect(fn () => $adapter->commit())
            ->toThrow(RuntimeException::class, 'Transactions are not supported in DB-less mode.');

        expect(fn () => $adapter->rollBack())
            ->toThrow(RuntimeException::class, 'Transactions are not supported in DB-less mode.');
    });

    it('returns default values for lastInsertId and isConnected', function () {
        $adapter = new DbLessAdapter();

        expect($adapter->lastInsertId())->toBe('');
        expect($adapter->isConnected())->toBeFalse();
    });
});
