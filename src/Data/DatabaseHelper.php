<?php declare(strict_types=1);

namespace Lalaz\Data;

use Lalaz\Data\Contracts\ConnectionAdapterInterface;

/**
 * Class DatabaseHelper
 *
 * Provides security helpers for SQL identifier and value quoting to prevent SQL injection.
 * Uses database-specific quoting mechanisms based on the active adapter.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class DatabaseHelper
{
    /**
     * Quotes a database identifier (table name, column name) based on the database driver.
     *
     * This prevents SQL injection when identifiers come from user input or variables.
     *
     * Examples:
     * - MySQL: `users`
     * - PostgreSQL: "users"
     * - SQLite: "users"
     *
     * @param string $identifier The identifier to quote (table, column, etc.)
     * @param ConnectionAdapterInterface|null $adapter Optional adapter (uses Lalaz::db() if null)
     * @return string The quoted identifier
     * @throws \RuntimeException If adapter doesn't support PDO
     */
    public static function quoteIdentifier(string $identifier, ?ConnectionAdapterInterface $adapter = null): string
    {
        if ($adapter === null) {
            $adapter = \Lalaz\Lalaz::db()->getAdapter();
        }

        // Validate identifier doesn't contain malicious characters
        if (!self::isValidIdentifier($identifier)) {
            throw new \InvalidArgumentException(
                "Invalid identifier: '{$identifier}'. Identifiers can only contain alphanumeric characters, underscores, and dots."
            );
        }

        // Get driver name
        $driver = self::getDriverName($adapter);

        // Quote based on driver
        return match($driver) {
            'mysql' => self::quoteMysqlIdentifier($identifier),
            'pgsql' => self::quotePostgresIdentifier($identifier),
            'sqlite' => self::quoteSqliteIdentifier($identifier),
            default => self::quoteGenericIdentifier($identifier)
        };
    }

    /**
     * Quotes multiple identifiers and joins them with a separator.
     *
     * Useful for column lists: quoteIdentifiers(['id', 'name']) => "`id`, `name`" (MySQL)
     *
     * @param array $identifiers Array of identifiers to quote
     * @param string $separator Separator to use (default: ', ')
     * @param ConnectionAdapterInterface|null $adapter Optional adapter
     * @return string Quoted identifiers joined by separator
     */
    public static function quoteIdentifiers(array $identifiers, string $separator = ', ', ?ConnectionAdapterInterface $adapter = null): string
    {
        $quoted = array_map(
            fn($id) => self::quoteIdentifier($id, $adapter),
            $identifiers
        );

        return implode($separator, $quoted);
    }

    /**
     * Quotes a qualified identifier (table.column).
     *
     * Example: quoteQualifiedIdentifier('users.email') => "`users`.`email`" (MySQL)
     *
     * @param string $qualifiedIdentifier Identifier with dots (e.g., "table.column")
     * @param ConnectionAdapterInterface|null $adapter Optional adapter
     * @return string Fully quoted identifier
     */
    public static function quoteQualifiedIdentifier(string $qualifiedIdentifier, ?ConnectionAdapterInterface $adapter = null): string
    {
        $parts = explode('.', $qualifiedIdentifier);
        $quotedParts = array_map(
            fn($part) => self::quoteIdentifier(trim($part), $adapter),
            $parts
        );

        return implode('.', $quotedParts);
    }

    /**
     * Validates that an identifier is safe (alphanumeric + underscore + dot).
     *
     * This is a defense-in-depth measure. Even though we quote identifiers,
     * we still validate them to prevent unexpected characters.
     *
     * @param string $identifier The identifier to validate
     * @return bool True if valid, false otherwise
     */
    private static function isValidIdentifier(string $identifier): bool
    {
        // Allow alphanumeric, underscore, and dot (for qualified names)
        // Do NOT allow backticks, quotes, semicolons, or other SQL special chars
        return preg_match('/^[a-zA-Z0-9_\.]+$/', $identifier) === 1;
    }

    /**
     * Gets the driver name from the adapter.
     *
     * @param ConnectionAdapterInterface $adapter The database adapter
     * @return string Driver name ('mysql', 'pgsql', 'sqlite', etc.)
     */
    private static function getDriverName(ConnectionAdapterInterface $adapter): string
    {
        // Fallback: detect from class name (safest approach)
        $className = get_class($adapter);

        if (str_contains($className, 'MysqlAdapter')) {
            return 'mysql';
        }

        if (str_contains($className, 'PostgresAdapter')) {
            return 'pgsql';
        }

        if (str_contains($className, 'SQLiteAdapter')) {
            return 'sqlite';
        }

        return 'unknown';
    }

    /**
     * Quotes a MySQL identifier using backticks.
     *
     * MySQL uses backticks for identifiers: `table_name`
     * Escapes existing backticks by doubling them: ``
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    private static function quoteMysqlIdentifier(string $identifier): string
    {
        // Escape existing backticks by doubling them
        $escaped = str_replace('`', '``', $identifier);
        return "`{$escaped}`";
    }

    /**
     * Quotes a PostgreSQL identifier using double quotes.
     *
     * PostgreSQL uses double quotes for case-sensitive identifiers: "table_name"
     * Escapes existing double quotes by doubling them: ""
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    private static function quotePostgresIdentifier(string $identifier): string
    {
        // Escape existing double quotes by doubling them
        $escaped = str_replace('"', '""', $identifier);
        return "\"{$escaped}\"";
    }

    /**
     * Quotes a SQLite identifier using double quotes.
     *
     * SQLite uses double quotes for identifiers: "table_name"
     * Escapes existing double quotes by doubling them: ""
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    private static function quoteSqliteIdentifier(string $identifier): string
    {
        // SQLite uses same quoting as PostgreSQL
        return self::quotePostgresIdentifier($identifier);
    }

    /**
     * Generic identifier quoting using double quotes (ANSI SQL standard).
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    private static function quoteGenericIdentifier(string $identifier): string
    {
        $escaped = str_replace('"', '""', $identifier);
        return "\"{$escaped}\"";
    }

    /**
     * Builds a safe DROP TABLE IF EXISTS statement.
     *
     * This is a convenience method that properly quotes the table name.
     *
     * @param string $tableName The table to drop
     * @param ConnectionAdapterInterface|null $adapter Optional adapter
     * @return string The SQL statement
     */
    public static function buildDropTableIfExists(string $tableName, ?ConnectionAdapterInterface $adapter = null): string
    {
        $quotedTable = self::quoteIdentifier($tableName, $adapter);
        return "DROP TABLE IF EXISTS {$quotedTable}";
    }

    /**
     * Builds a safe SELECT 1 FROM table statement for existence check.
     *
     * @param string $tableName The table to check
     * @param ConnectionAdapterInterface|null $adapter Optional adapter
     * @return string The SQL statement
     */
    public static function buildTableExistsCheck(string $tableName, ?ConnectionAdapterInterface $adapter = null): string
    {
        $quotedTable = self::quoteIdentifier($tableName, $adapter);
        return "SELECT 1 FROM {$quotedTable} LIMIT 1";
    }
}
