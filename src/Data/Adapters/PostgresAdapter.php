<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

use PDO;
use PDOException;
use PDOStatement;
use Lalaz\Data\Contracts\ConnectionAdapterInterface;

/**
 * Class PostgresAdapter
 *
 * PostgreSQL implementation for the connection adapter using PDO.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class PostgresAdapter implements ConnectionAdapterInterface
{
    protected ?PDO $pdo = null;
    protected array $config;
    protected ?string $lastInsertedId = null;
    /** @var callable(string, string, string, array): PDO */
    private $pdoFactory;

    /**
     * PostgresAdapter constructor.
     *
     * @param array $config Configuration array with keys:
     *                      - host: Database host (default: localhost)
     *                      - port: Database port (default: 5432)
     *                      - database: Database name
     *                      - user: Database user
     *                      - password: Database password
     *                      - schema: Schema name (default: public)
     */
    public function __construct(array $config, ?callable $pdoFactory = null)
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 5432,
            'schema' => 'public',
        ], $config);
        $this->pdoFactory = $pdoFactory ?? static fn (string $dsn, string $user, string $password, array $options): PDO => new PDO($dsn, $user, $password, $options);
    }

    /**
     * Establishes the PostgreSQL connection.
     *
     * @return void
     * @throws \RuntimeException If connection fails
     */
    public function connect(): void
    {
        if ($this->pdo) return;

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database']
        );

        try {
            $factory = $this->pdoFactory;
            $this->pdo = $factory(
                $dsn,
                $this->config['user'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, // Important for PostgreSQL
                ]
            );

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Set search path to specified schema
            if (!empty($this->config['schema'])) {
                $this->pdo->exec("SET search_path TO {$this->config['schema']}, public");
            }
        } catch (PDOException $e) {
            throw new \RuntimeException(
                'Failed to connect to PostgreSQL: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Execute a query and return the result.
     *
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return mixed PDOStatement or result
     */
    public function query(string $sql, array $bindings = []): mixed
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($bindings);

        // Capture RETURNING clause results for lastInsertId
        if (stripos($sql, 'RETURNING') !== false) {
            $result = $stmt->fetch();
            if ($result && isset($result['id'])) {
                $this->lastInsertedId = (string)$result['id'];
            }
        }

        return $stmt;
    }

    /**
     * Execute a statement without returning results.
     *
     * @param string $sql SQL statement
     * @param array $bindings Parameter bindings
     * @return void
     */
    public function exec(string $sql, array $bindings = []): void
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($bindings);

        // Capture RETURNING clause results for lastInsertId
        if (stripos($sql, 'RETURNING') !== false) {
            $result = $stmt->fetch();
            if ($result && isset($result['id'])) {
                $this->lastInsertedId = (string)$result['id'];
            }
        }
    }

    /**
     * Begin a transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get the last inserted ID.
     *
     * PostgreSQL Note: This requires using RETURNING clause in INSERT statements
     * or providing the sequence name.
     *
     * @param string|null $sequenceName Optional sequence name
     * @return string
     */
    public function lastInsertId(?string $sequenceName = null): string
    {
        if ($this->lastInsertedId !== null) {
            return $this->lastInsertedId;
        }

        if ($sequenceName) {
            return $this->pdo->lastInsertId($sequenceName);
        }

        return $this->pdo->lastInsertId();
    }

    /**
     * Prepare a SQL statement.
     *
     * @param string $sql SQL query
     * @return PDOStatement
     */
    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * Check if connected.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Get the PDO instance.
     *
     * @return PDO|null
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }
}
