<?php declare(strict_types=1);

namespace Lalaz\Data;

use PDOStatement;
use Lalaz\Data\Contracts\ConnectionAdapterInterface;

/**
 * Class Database
 *
 * This class provides a generic abstraction layer for database operations, delegating all behavior
 * to a configurable adapter. This enables Lalaz to operate in both traditional (e.g. MySQL) and
 * DB-less environments, without modifying the application logic.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Database
{
    /**
     * The underlying database connection adapter.
     *
     * @var ConnectionAdapterInterface
     */
    private ConnectionAdapterInterface $adapter;

    /**
     * Constructor for the Database class.
     *
     * Initializes the adapter used to manage database operations.
     *
     * @param ConnectionAdapterInterface $adapter An implementation of the connection adapter.
     */
    public function __construct(ConnectionAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->adapter->connect();
    }

    /**
     * Begins a new database transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function beginTransaction(): bool
    {
        return $this->adapter->beginTransaction();
    }

    /**
     * Commits the current database transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function commit(): bool
    {
        return $this->adapter->commit();
    }

    /**
     * Rolls back the current database transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function rollBack(): bool
    {
        return $this->adapter->rollBack();
    }

    /**
     * Prepares an SQL statement for execution.
     *
     * @param string $query The SQL query to prepare.
     * @return PDOStatement The prepared statement.
     */
    public function prepare(string $query): PDOStatement
    {
        return $this->adapter->prepare($query);
    }

    /**
     * Executes a SQL query and returns the result set.
     *
     * @param string $query    The SQL query to execute.
     * @param array  $bindings Optional query bindings.
     * @return mixed The result set or false on failure.
     */
    public function query(string $query, array $bindings = []): mixed
    {
        return $this->adapter->query($query, $bindings);
    }

    /**
     * Executes a SQL statement without returning a result set.
     *
     * @param string $query    The SQL statement to execute.
     * @param array  $bindings Optional bindings for the SQL statement.
     * @return void
     */
    public function exec(string $query, array $bindings = []): void
    {
        $this->adapter->exec($query, $bindings);
    }

    /**
     * Returns the last inserted ID for an auto-increment column.
     *
     * @return string The last insert ID.
     */
    public function lastInsertId(): string
    {
        return $this->adapter->lastInsertId();
    }

    /**
     * Checks whether the adapter is connected to a database.
     *
     * @return bool True if connected, false otherwise.
     */
    public function isConnected(): bool
    {
        return $this->adapter->isConnected();
    }

    /**
     * Returns the underlying connection adapter.
     *
     * @return ConnectionAdapterInterface The connection adapter instance.
     */
    public function getAdapter(): ConnectionAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Logs a debug message with timestamp to STDOUT.
     *
     * @param string $message The message to log.
     * @return void
     */
    private function log(string $message): void
    {
        echo "[" . date("Y-m-d H:i:s") . "] - " . $message . PHP_EOL;
    }
}
