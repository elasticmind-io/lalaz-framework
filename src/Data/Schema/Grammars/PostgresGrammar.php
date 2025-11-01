<?php declare(strict_types=1);

namespace Lalaz\Data\Schema\Grammars;

use Lalaz\Data\Schema\Blueprint;
use Lalaz\Data\Schema\Contracts\SchemaGrammarInterface;

/**
 * Class PostgresGrammar
 *
 * PostgreSQL-specific implementation of the schema grammar.
 * Generates PostgreSQL-compatible SQL statements for table creation and modification.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class PostgresGrammar implements SchemaGrammarInterface
{
    /**
     * Compile a CREATE TABLE statement.
     *
     * @param Blueprint $blueprint
     * @return string
     */
    public function compileCreate(Blueprint $blueprint): string
    {
        $tableName = $blueprint->getTableName();
        $columns = $blueprint->getCompiledColumns();
        $indexes = $blueprint->getCompiledIndexes();
        $foreignKeys = $blueprint->getCompiledForeignKeys();

        $definitions = array_merge($columns, $indexes, $foreignKeys);
        $definitionsStr = implode(",\n", $definitions);

        // PostgreSQL doesn't support table options like MySQL ENGINE/CHARSET
        return "CREATE TABLE {$tableName} (\n{$definitionsStr}\n);";
    }

    /**
     * Compile a DROP TABLE IF EXISTS statement.
     *
     * @param string $table
     * @return string
     */
    public function compileDrop(string $table): string
    {
        return "DROP TABLE IF EXISTS {$table};";
    }

    /**
     * Compile auto-incrementing column (uses BIGSERIAL).
     *
     * @param string $column
     * @return string
     */
    public function compileIncrementsColumn(string $column): string
    {
        // BIGSERIAL = BIGINT with auto-increment
        return "{$column} BIGSERIAL PRIMARY KEY";
    }

    /**
     * Compile big integer column.
     *
     * @param string $column
     * @param bool $unsigned PostgreSQL doesn't have UNSIGNED, use CHECK constraint
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileBigIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} BIGINT";

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        // Add CHECK constraint for unsigned behavior
        if ($unsigned) {
            $definition .= " CHECK ({$column} >= 0)";
        }

        return $definition;
    }

    /**
     * Compile integer column.
     *
     * @param string $column
     * @param bool $unsigned
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} INTEGER";

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        if ($unsigned) {
            $definition .= " CHECK ({$column} >= 0)";
        }

        return $definition;
    }

    /**
     * Compile small integer column.
     *
     * @param string $column
     * @param bool $unsigned
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileSmallIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} SMALLINT";

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        if ($unsigned) {
            $definition .= " CHECK ({$column} >= 0)";
        }

        return $definition;
    }

    /**
     * Compile tiny integer column (PostgreSQL doesn't have TINYINT, use SMALLINT).
     *
     * @param string $column
     * @param bool $unsigned
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileTinyIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        // PostgreSQL doesn't have TINYINT, use SMALLINT
        $definition = "{$column} SMALLINT";

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        if ($unsigned) {
            $definition .= " CHECK ({$column} >= 0)";
        }

        return $definition;
    }

    /**
     * Compile string column.
     *
     * @param string $column
     * @param int $length
     * @param bool $nullable
     * @param mixed $default
     * @param string|null $charset Ignored in PostgreSQL (encoding is at database level)
     * @param string|null $collation Ignored in PostgreSQL
     * @return string
     */
    public function compileStringColumn(
        string $column,
        int $length,
        bool $nullable,
        $default,
        ?string $charset = null,
        ?string $collation = null
    ): string {
        $definition = "{$column} VARCHAR({$length})";
        $definition .= $this->compileNullableAndDefault($nullable, $default);

        // PostgreSQL handles charset/collation at database level, ignore these parameters
        return $definition;
    }

    /**
     * Compile text column.
     *
     * @param string $column
     * @param bool $nullable
     * @param string|null $charset Ignored
     * @param string|null $collation Ignored
     * @return string
     */
    public function compileTextColumn(
        string $column,
        bool $nullable,
        ?string $charset = null,
        ?string $collation = null
    ): string {
        $definition = "{$column} TEXT";
        $definition .= $this->compileNullableAndDefault($nullable, null);
        return $definition;
    }

    /**
     * Compile datetime column.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDatetimeColumn(string $column, bool $nullable, $default): string
    {
        $definition = "{$column} TIMESTAMP";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile date column.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDateColumn(string $column, bool $nullable, $default): string
    {
        $definition = "{$column} DATE";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile time column.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileTimeColumn(string $column, bool $nullable, $default): string
    {
        $definition = "{$column} TIME";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile timestamp column.
     *
     * Note: PostgreSQL doesn't support ON UPDATE CURRENT_TIMESTAMP.
     * Use triggers or application logic for update timestamps.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @param bool $onUpdateCurrentTimestamp Ignored in PostgreSQL (requires trigger)
     * @return string
     */
    public function compileTimestampColumn(
        string $column,
        bool $nullable,
        $default,
        bool $onUpdateCurrentTimestamp = false
    ): string {
        $definition = "{$column} TIMESTAMP";
        $definition .= $this->compileNullableAndDefault($nullable, $default);

        // ON UPDATE CURRENT_TIMESTAMP not supported in PostgreSQL
        // Would require a trigger - not implemented here
        return $definition;
    }

    /**
     * Compile boolean column (PostgreSQL has native BOOLEAN type).
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileBooleanColumn(string $column, bool $nullable, $default): string
    {
        $definition = "{$column} BOOLEAN";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile float column.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileFloatColumn(string $column, bool $nullable, $default): string
    {
        $definition = "{$column} REAL";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile double column.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDoubleColumn(string $column, bool $nullable, $default): string
    {
        $definition = "{$column} DOUBLE PRECISION";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile decimal column.
     *
     * @param string $column
     * @param int $precision
     * @param int $scale
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDecimalColumn(
        string $column,
        int $precision,
        int $scale,
        bool $nullable,
        $default
    ): string {
        $definition = "{$column} NUMERIC({$precision}, {$scale})";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile binary column.
     *
     * @param string $column
     * @param int|null $length
     * @param bool $nullable
     * @return string
     */
    public function compileBinaryColumn(string $column, ?int $length, bool $nullable): string
    {
        // PostgreSQL uses BYTEA for binary data
        $definition = "{$column} BYTEA";
        $definition .= $this->compileNullableAndDefault($nullable, null);
        return $definition;
    }

    /**
     * Compile enum column.
     *
     * PostgreSQL supports CREATE TYPE for enums, but for simplicity,
     * we'll use TEXT with CHECK constraint (like SQLite).
     *
     * @param string $column
     * @param array $allowed
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileEnumColumn(string $column, array $allowed, bool $nullable, $default): string
    {
        $definition = "{$column} TEXT";
        $definition .= $this->compileNullableAndDefault($nullable, $default);

        // Add CHECK constraint for enum values
        $allowedValues = array_map(fn($v) => "'{$v}'", $allowed);
        $definition .= " CHECK({$column} IN (" . implode(', ', $allowedValues) . "))";

        return $definition;
    }

    /**
     * Compile JSON column.
     *
     * @param string $column
     * @param bool $nullable
     * @return string
     */
    public function compileJsonColumn(string $column, bool $nullable): string
    {
        // PostgreSQL has both JSON and JSONB (binary JSON, better performance)
        $definition = "{$column} JSONB";
        $definition .= $this->compileNullableAndDefault($nullable, null);
        return $definition;
    }

    /**
     * Compile timestamps (created_at, updated_at).
     *
     * @return array
     */
    public function compileTimestamps(): array
    {
        return [
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            // Note: ON UPDATE requires trigger in PostgreSQL
        ];
    }

    /**
     * Compile soft deletes (deleted_at).
     *
     * @return string
     */
    public function compileSoftDeletes(): string
    {
        return 'deleted_at TIMESTAMP';
    }

    /**
     * Compile primary key constraint.
     *
     * @param array $columns
     * @return string
     */
    public function compilePrimaryKey(array $columns): string
    {
        $columnList = implode(', ', $columns);
        return "PRIMARY KEY ({$columnList})";
    }

    /**
     * Compile unique constraint.
     *
     * @param string $name
     * @param array $columns
     * @return string
     */
    public function compileUnique(string $name, array $columns): string
    {
        $columnList = implode(', ', $columns);
        return "CONSTRAINT {$name} UNIQUE ({$columnList})";
    }

    /**
     * Compile index.
     *
     * Note: PostgreSQL indexes are typically created separately from table definition.
     * Return empty string for inline definition.
     *
     * @param string $name
     * @param array $columns
     * @return string
     */
    public function compileIndex(string $name, array $columns): string
    {
        // PostgreSQL creates indexes separately
        // Return empty string for inline definition
        return '';
    }

    /**
     * Compile foreign key constraint.
     *
     * @param string $name
     * @param string $column
     * @param string $referencedTable
     * @param string $referencedColumn
     * @param string|null $onDelete
     * @param string|null $onUpdate
     * @return string
     */
    public function compileForeignKey(
        string $name,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): string {
        $definition = "CONSTRAINT {$name} FOREIGN KEY ({$column}) REFERENCES {$referencedTable}({$referencedColumn})";

        if ($onDelete) {
            $definition .= " ON DELETE {$onDelete}";
        }

        if ($onUpdate) {
            $definition .= " ON UPDATE {$onUpdate}";
        }

        return $definition;
    }

    /**
     * Compile table options.
     *
     * PostgreSQL doesn't support MySQL-style table options like ENGINE or CHARSET.
     * These are database-level settings.
     *
     * @param array $options
     * @return string
     */
    public function compileTableOptions(array $options): string
    {
        // Ignore MySQL-specific options
        return '';
    }

    /**
     * Get the database driver name.
     *
     * @return string
     */
    public function getDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * Helper: Compile nullable and default clauses.
     *
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    private function compileNullableAndDefault(bool $nullable, $default): string
    {
        $clause = $nullable ? '' : ' NOT NULL';

        if ($default !== null) {
            if (is_string($default) && strtoupper($default) !== 'CURRENT_TIMESTAMP') {
                $clause .= " DEFAULT '{$default}'";
            } else {
                $clause .= " DEFAULT {$default}";
            }
        }

        return $clause;
    }
}
