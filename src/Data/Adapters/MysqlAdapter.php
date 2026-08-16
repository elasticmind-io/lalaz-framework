<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

use PDO;
use Lalaz\Data\Contracts\ConnectionAdapterInterface;

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
    /** @var callable(string, string, string, array): PDO */
    private $pdoFactory;

    public function __construct(array $config, ?callable $pdoFactory = null)
    {
        $this->config = $config;
        $this->pdoFactory = $pdoFactory ?? static fn (string $dsn, string $user, string $password, array $options): PDO => new PDO($dsn, $user, $password, $options);
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

        $factory = $this->pdoFactory;
        $this->pdo = $factory(
            $dsn,
            $this->config['user'],
            $this->config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function query(string $sql, array $bindings = []): mixed
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
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
