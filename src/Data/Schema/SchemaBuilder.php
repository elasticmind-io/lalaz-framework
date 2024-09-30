<?php declare(strict_types=1);

namespace Lalaz\Data\Schema;

use Lalaz\Lalaz;

/**
 * Class SchemaBuilder
 *
 * This class provides static methods to create and drop database tables using the `Blueprint` class.
 * It serves as a schema builder for database migrations, allowing you to define table structures
 * and execute the necessary SQL statements to modify the database schema.
 *
 * @package Lalaz\Data\Schema
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
        $callback($blueprint);

        $sql= $blueprint->toSql();

        Lalaz::db()->exec($sql);
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
