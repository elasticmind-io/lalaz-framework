<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

use PDO;
use PDOException;
use PDOStatement;
use Lalaz\Data\Contracts\ConnectionAdapterInterface;

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
    /** @var callable(string, array): PDO */
    private $pdoFactory;

    /**
     * SQLiteAdapter constructor.
     *
     * @param array $config Must contain 'path' => '/full/path/to/sqlite.db'
     */
    public function __construct(array $config, ?callable $pdoFactory = null)
    {
        if (empty($config['path'])) {
            throw new \InvalidArgumentException('SQLite database path is required.');
        }

        $this->path = $config['path'];
        $this->pdoFactory = $pdoFactory ?? static fn (string $dsn, array $options = []): PDO => new PDO($dsn, null, null, $options);

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
            $factory = $this->pdoFactory;
            $this->pdo = $factory("sqlite:{$this->path}", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

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
