<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Class SQLiteAdapter
 *
 * A connection adapter that uses SQLite via PDO.
 *
 * @package elasticmind\lalaz-framework
 */
class SQLiteAdapter implements ConnectionAdapterInterface
{
    protected ?PDO $pdo = null;
    protected string $path;

    /**
     * SQLiteAdapter constructor.
     *
     * @param array $config Must contain 'path' => '/full/path/to/sqlite.db'
     */
    public function __construct(array $config)
    {
        if (empty($config['path'])) {
            throw new \InvalidArgumentException('SQLite database path is required.');
        }

        $this->path = $config['path'];

        // Defensive: create the .sqlite file if it doesn't exist
        if (!file_exists($this->path)) {
            touch($this->path);
        }
    }

    /**
     * Establishes the SQLite connection.
     *
     * @return void
     */
    public function connect(): void
    {
        if ($this->pdo) return;

        try {
            $this->pdo = new PDO("sqlite:{$this->path}");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to connect to SQLite: ' . $e->getMessage(), 500, $e);
        }
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

    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Returns the underlying PDO instance.
     *
     * @return PDO|null
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }
}
