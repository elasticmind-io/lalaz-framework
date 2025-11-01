<?php declare(strict_types=1);

namespace Lalaz\Data\Schema;

use Lalaz\Lalaz;
use Lalaz\Data\Schema\Grammars\MySqlGrammar;
use Lalaz\Data\Schema\Grammars\SQLiteGrammar;
use Lalaz\Data\Schema\Grammars\PostgresGrammar;

/**
 * Class SchemaBuilder
 *
 * This class provides static methods to create and drop database tables using the `Blueprint` class.
 * It serves as a schema builder for database migrations, allowing you to define table structures
 * and execute the necessary SQL statements to modify the database schema.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class SchemaBuilder
{
    /**
     * Creates a new table in the database.
     *
     * This method uses the `Blueprint` class to define the table's schema.
     * The callback function is used to add columns and define the table structure.
     *
     * @param string   $table    The name of the table to create.
     * @param callable $callback A callback function that receives a `Blueprint` instance to define the table schema.
     *
     * @return void
     */
    public static function create($table, $callback)
    {
        $blueprint = new Blueprint($table);

        // Detect database driver and inject appropriate grammar
        $grammar = self::getGrammar();
        $blueprint->setGrammar($grammar);

        $callback($blueprint);

        $sql = $blueprint->toSql();

        Lalaz::db()->exec($sql);
    }

    /**
     * Detects the current database driver and returns the appropriate grammar instance.
     *
     * @return \Lalaz\Data\Schema\Contracts\SchemaGrammarInterface
     * @throws \RuntimeException if the driver is not supported
     */
    private static function getGrammar()
    {
        $db = Lalaz::db();
        $adapter = $db->getAdapter();

        // Get the driver name from the adapter
        $driver = self::detectDriver($adapter);

        return match($driver) {
            'mysql' => new MySqlGrammar(),
            'sqlite' => new SQLiteGrammar(),
            'pgsql' => new PostgresGrammar(),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}")
        };
    }

    /**
     * Detects the database driver from the adapter instance.
     *
     * @param mixed $adapter The database adapter instance
     * @return string The driver name (mysql, sqlite, pgsql, etc.)
     */
    private static function detectDriver($adapter): string
    {
        $adapterClass = get_class($adapter);

        // Extract driver from adapter class name
        if (str_contains($adapterClass, 'MysqlAdapter')) {
            return 'mysql';
        }

        if (str_contains($adapterClass, 'SQLiteAdapter')) {
            return 'sqlite';
        }

        if (str_contains($adapterClass, 'PostgresAdapter')) {
            return 'pgsql';
        }

        // Fallback: check PDO driver if adapter has PDO connection
        if (method_exists($adapter, 'getPdo')) {
            $pdo = $adapter->getPdo();
            if ($pdo instanceof \PDO) {
                return $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            }
        }

        return 'unknown';
    }

    /**
     * Drops a table from the database if it exists.
     *
     * @param string $table The name of the table to drop.
     *
     * @return void
     */
    public static function dropIfExists($table)
    {
        $sql = "DROP TABLE IF EXISTS $table";
        Lalaz::db()->exec($sql);
    }
}
