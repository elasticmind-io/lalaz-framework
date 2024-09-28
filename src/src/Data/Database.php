<?php declare(strict_types=1);

namespace Lalaz\Data;

/**
 * Class Database
 *
 * This class provides a simple abstraction over PHP's PDO class, enabling database interactions
 * such as preparing statements, executing queries, and handling exceptions. It encapsulates the PDO instance
 * and offers convenient methods for common database operations.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Data
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class Database
{
    /** @var \PDO $pdo The PDO instance used for database operations */
    private \PDO $pdo;

    /**
     * Constructor for the Database class.
     *
     * Initializes the PDO instance with the provided database configuration.
     *
     * @param array $dbConfig An array containing database configuration with keys 'dsn', 'user', and 'password'.
     */
    public function __construct($dbConfig = [])
    {
        $dbDsn = $dbConfig['dsn'] ?? '';
        $username = $dbConfig['user'] ?? '';
        $password = $dbConfig['password'] ?? '';

        $this->pdo = new \PDO($dbDsn, $username, $password);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Prepares an SQL statement for execution.
     *
     * @param string $query The SQL query to prepare.
     * @return \PDOStatement The prepared statement.
     */
    public function prepare($query): \PDOStatement
    {
        return $this->pdo->prepare($query);
    }

    /**
     * Executes an SQL query and returns the result.
     *
     * @param string $query The SQL query to execute.
     * @return mixed The result set as a PDOStatement, or false on failure.
     */
    public function query($query): mixed
    {
        return $this->pdo->query($query);
    }

    /**
     * Executes an SQL statement and returns the number of affected rows.
     *
     * @param string $query The SQL statement to execute.
     * @return void
     */
    public function exec($query): void
    {
        $this->pdo->exec($query);
    }

    /**
     * Return the last insert id into the database.
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Logs a message with a timestamp.
     *
     * @param string $message The message to log.
     * @return void
     */
    private function log($message)
    {
        echo "[" . date("Y-m-d H:i:s") . "] - " . $message . PHP_EOL;
    }
}
