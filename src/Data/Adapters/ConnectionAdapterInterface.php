<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

/**
 * Interface ConnectionAdapterInterface
 *
 * Defines the contract for database adapters used by ActiveRecord.
 *
 * @package Lalaz\Database
 */
interface ConnectionAdapterInterface
{
    public function connect(): void;
    public function query(string $sql, array $bindings = []): mixed;
    public function exec(string $sql, array $bindings = []): void;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
    public function lastInsertId(): string;
    public function prepare(string $sql): \PDOStatement;
    public function isConnected(): bool;
}
