<?php

use Lalaz\Data\Contracts\ConnectionAdapterInterface;
use Lalaz\Data\DatabaseHelper;

defineTestAdapters();

describe('DatabaseHelper', function () {
    it('quotes mysql identifiers with backticks', function () {
        $adapter = new DummyMysqlAdapter();
        expect(DatabaseHelper::quoteIdentifier('users', $adapter))->toBe('`users`');
    });

    it('quotes postgres identifiers with double quotes', function () {
        $adapter = new DummyPostgresAdapter();
        expect(DatabaseHelper::quoteIdentifier('users', $adapter))->toBe('"users"');
    });

    it('quotes sqlite identifiers with double quotes', function () {
        $adapter = new DummySQLiteAdapter();
        expect(DatabaseHelper::quoteIdentifier('users', $adapter))->toBe('"users"');
    });

    it('quotes qualified identifiers segment by segment', function () {
        $adapter = new DummyMysqlAdapter();
        expect(DatabaseHelper::quoteQualifiedIdentifier('schema.users', $adapter))->toBe('`schema`.`users`');
    });

    it('throws when identifier contains disallowed characters', function () {
        $adapter = new DummyMysqlAdapter();

        expect(fn () => DatabaseHelper::quoteIdentifier('users; DROP TABLE', $adapter))
            ->toThrow(InvalidArgumentException::class);
    });

    it('falls back to generic quoting for unknown drivers', function () {
        $adapter = new DummyUnknownAdapter();
        expect(DatabaseHelper::quoteIdentifier('users', $adapter))->toBe('"users"');
    });

    it('builds drop table statements with quoted identifiers', function () {
        $adapter = new DummyMysqlAdapter();
        expect(DatabaseHelper::buildDropTableIfExists('users', $adapter))
            ->toBe('DROP TABLE IF EXISTS `users`');
    });
});

function defineTestAdapters(): void
{
    if (class_exists('DummyMysqlAdapter')) {
        return;
    }

    abstract class DummyConnectionAdapter implements ConnectionAdapterInterface
    {
        public function connect(): void {}

        public function query(string $sql, array $bindings = []): mixed
        {
            return null;
        }

        public function exec(string $sql, array $bindings = []): void {}

        public function beginTransaction(): bool
        {
            return false;
        }

        public function commit(): bool
        {
            return false;
        }

        public function rollBack(): bool
        {
            return false;
        }

        public function lastInsertId(): string
        {
            return '0';
        }

        public function prepare(string $sql): \PDOStatement
        {
            throw new BadMethodCallException('Not implemented');
        }

        public function isConnected(): bool
        {
            return false;
        }
    }

    class DummyMysqlAdapter extends DummyConnectionAdapter {}
    class DummyPostgresAdapter extends DummyConnectionAdapter {}
    class DummySQLiteAdapter extends DummyConnectionAdapter {}
    class DummyUnknownAdapter extends DummyConnectionAdapter {}
}
