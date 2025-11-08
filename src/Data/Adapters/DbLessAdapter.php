<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

use Lalaz\Data\Contracts\ConnectionAdapterInterface;

class DbLessAdapter implements ConnectionAdapterInterface
{
    public function connect(): void {}

    public function query(string $sql, array $bindings = []): mixed
    {
        throw new \RuntimeException('Query not available: running in DB-less mode.');
    }

    public function exec(string $sql, array $bindings = []): void
    {
        throw new \RuntimeException('Exec not available: running in DB-less mode.');
    }

    public function beginTransaction(): bool
    {
        throw new \RuntimeException('Transactions are not supported in DB-less mode.');
    }

    public function commit(): bool
    {
        throw new \RuntimeException('Transactions are not supported in DB-less mode.');
    }

    public function rollBack(): bool
    {
        throw new \RuntimeException('Transactions are not supported in DB-less mode.');
    }

    public function lastInsertId(): string
    {
        return '';
    }

    public function prepare(string $sql): \PDOStatement
    {
        throw new \RuntimeException('Prepare not supported in DB-less mode.');
    }

    public function isConnected(): bool
    {
        return false;
    }
}
