<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

use PDO;

/**
 * Class MysqlAdapter
 *
 * Provides a MySQL implementation for the connection adapter.
 *
 * @package Lalaz\Database
 */
class MysqlAdapter implements ConnectionAdapterInterface
{
    protected ?PDO $pdo = null;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(): void
    {
        if ($this->pdo) return;

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->config['host'],
            $this->config['port'],
            $this->config['database']
        );

        $this->pdo = new PDO(
            $dsn,
            $this->config['user'],
            $this->config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function query(string $sql, array $bindings = []): mixed
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    public function exec(string $sql, array $bindings = []): void
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($bindings);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function prepare(string $sql): \PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }
}
