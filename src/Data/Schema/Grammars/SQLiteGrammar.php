<?php declare(strict_types=1);

namespace Lalaz\Data\Schema\Grammars;

use Lalaz\Data\Schema\Blueprint;
use Lalaz\Data\Contracts\SchemaGrammarInterface;

/**
 * Class SQLiteGrammar
 *
 * SQLite-specific implementation of the schema grammar.
 * Generates SQLite-compatible SQL statements for table creation and modification.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class SQLiteGrammar implements SchemaGrammarInterface
{
    /**
     * Compile a CREATE TABLE statement from the blueprint.
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

        // SQLite ignores table options like ENGINE, CHARSET
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
     * Compile an auto-incrementing column definition.
     * SQLite uses INTEGER PRIMARY KEY AUTOINCREMENT.
     *
     * @param string $column
     * @return string
     */
    public function compileIncrementsColumn(string $column): string
    {
        return "{$column} INTEGER PRIMARY KEY AUTOINCREMENT";
    }

    /**
     * Compile a big integer column definition.
     * SQLite doesn't have BIGINT, uses INTEGER. Also doesn't support UNSIGNED.
     *
     * @param string $column
     * @param bool $unsigned (ignored in SQLite)
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileBigIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} INTEGER";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile an integer column definition.
     * SQLite doesn't support UNSIGNED.
     *
     * @param string $column
     * @param bool $unsigned (ignored in SQLite)
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} INTEGER";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile a small integer column definition.
     * SQLite uses INTEGER for all integer types.
     *
     * @param string $column
     * @param bool $unsigned (ignored in SQLite)
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileSmallIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} INTEGER";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile a tiny integer column definition.
     * SQLite uses INTEGER for all integer types.
     *
     * @param string $column
     * @param bool $unsigned (ignored in SQLite)
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileTinyIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} INTEGER";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile a string/varchar column definition.
     * SQLite doesn't enforce VARCHAR length, but we keep it for compatibility.
     * Charset and collation are ignored.
     *
     * @param string $column
     * @param int $length
     * @param bool $nullable
     * @param mixed $default
     * @param string|null $charset (ignored in SQLite)
     * @param string|null $collation (ignored in SQLite)
     * @return string
     */
    public function compileStringColumn(string $column, int $length, bool $nullable, $default, ?string $charset, ?string $collation): string
    {
        $definition = "{$column} VARCHAR({$length})";
        $definition .= $this->compileNullableAndDefault($nullable, $default);
        return $definition;
    }

    /**
     * Compile a text column definition.
     * Charset and collation are ignored in SQLite.
     *
     * @param string $column
     * @param bool $nullable
     * @param string|null $charset (ignored in SQLite)
     * @param string|null $collation (ignored in SQLite)
     * @return string
     */
    public function compileTextColumn(string $column, bool $nullable, ?string $charset, ?string $collation): string
    {
        $definition = "{$column} TEXT";
        $definition .= $this->compileNullableAndDefault($nullable, null);
        return $definition;
    }

    /**
     * Compile a datetime column definition.
     * SQLite stores datetime as TEXT.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDatetimeColumn(string $column, bool $nullable, $default): string
    {
        return "{$column} DATETIME" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a date column definition.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDateColumn(string $column, bool $nullable, $default): string
    {
        return "{$column} DATE" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a time column definition.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileTimeColumn(string $column, bool $nullable, $default): string
    {
        return "{$column} TIME" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a timestamp column definition.
     * SQLite doesn't support ON UPDATE CURRENT_TIMESTAMP.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @param bool $onUpdateCurrentTimestamp (ignored in SQLite)
     * @return string
     */
    public function compileTimestampColumn(string $column, bool $nullable, $default, bool $onUpdateCurrentTimestamp): string
    {
        $definition = "{$column} TIMESTAMP";
        $definition .= $this->compileNullableAndDefault($nullable, $default);

        // SQLite doesn't support ON UPDATE, would need triggers
        // Ignoring $onUpdateCurrentTimestamp parameter

        return $definition;
    }

    /**
     * Compile a boolean column definition.
     * SQLite uses INTEGER (0 or 1) for boolean.
     *
     * @param string $column
     * @param bool $nullable
     * @param bool $default
     * @return string
     */
    public function compileBooleanColumn(string $column, bool $nullable, bool $default): string
    {
        $defaultValue = $default ? '1' : '0';
        return "{$column} INTEGER" . $this->compileNullableAndDefault($nullable, $defaultValue);
    }

    /**
     * Compile a float column definition.
     * SQLite uses REAL for floating point.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileFloatColumn(string $column, bool $nullable, $default): string
    {
        return "{$column} REAL" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a double column definition.
     * SQLite uses REAL for floating point.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDoubleColumn(string $column, bool $nullable, $default): string
    {
        return "{$column} REAL" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a decimal column definition.
     * SQLite doesn't have true DECIMAL, uses NUMERIC.
     *
     * @param string $column
     * @param int $precision
     * @param int $scale
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDecimalColumn(string $column, int $precision, int $scale, bool $nullable, $default): string
    {
        return "{$column} NUMERIC" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a binary column definition.
     * SQLite uses BLOB for binary data.
     *
     * @param string $column
     * @param int $length (ignored in SQLite)
     * @param bool $nullable
     * @return string
     */
    public function compileBinaryColumn(string $column, int $length, bool $nullable): string
    {
        return "{$column} BLOB" . $this->compileNullableAndDefault($nullable, null);
    }

    /**
     * Compile an enum column definition.
     * SQLite doesn't have ENUM type, we use TEXT with CHECK constraint.
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

        // Add CHECK constraint for allowed values
        $allowedValues = implode("', '", $allowed);
        $definition .= " CHECK({$column} IN ('{$allowedValues}'))";

        return $definition;
    }

    /**
     * Compile a JSON column definition.
     * SQLite stores JSON as TEXT.
     *
     * @param string $column
     * @param bool $nullable
     * @return string
     */
    public function compileJsonColumn(string $column, bool $nullable): string
    {
        return "{$column} TEXT" . $this->compileNullableAndDefault($nullable, null);
    }

    /**
     * Compile timestamps (created_at and updated_at) columns.
     * SQLite doesn't support ON UPDATE, so updated_at won't auto-update.
     *
     * @return array
     */
    public function compileTimestamps(): array
    {
        return [
            "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
    }

    /**
     * Compile a primary key constraint.
     *
     * @param array $columns
     * @return string
     */
    public function compilePrimaryKey(array $columns): string
    {
        $columnsList = implode(', ', $columns);
        return "PRIMARY KEY ({$columnsList})";
    }

    /**
     * Compile a unique constraint.
     *
     * @param string $name
     * @param array $columns
     * @return string
     */
    public function compileUnique(string $name, array $columns): string
    {
        $columnsList = implode(', ', $columns);
        return "UNIQUE ({$columnsList})";
    }

    /**
     * Compile an index.
     * Note: SQLite CREATE INDEX must be separate from CREATE TABLE.
     * This returns empty string as indexes should be created separately.
     *
     * @param string $name
     * @param array $columns
     * @return string
     */
    public function compileIndex(string $name, array $columns): string
    {
        // In SQLite, indexes are created with separate CREATE INDEX statements
        // We'll return empty here and handle it differently in the Blueprint
        return '';
    }

    /**
     * Compile a foreign key constraint.
     *
     * @param string $name
     * @param string $column
     * @param string $referencedTable
     * @param string $referencedColumn
     * @param string $onDelete
     * @param string $onUpdate
     * @return string
     */
    public function compileForeignKey(string $name, string $column, string $referencedTable, string $referencedColumn, string $onDelete, string $onUpdate): string
    {
        // SQLite foreign keys don't use CONSTRAINT name the same way
        return "FOREIGN KEY ({$column}) REFERENCES {$referencedTable}({$referencedColumn}) ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
    }

    /**
     * Compile table options.
     * SQLite ignores ENGINE, CHARSET, COLLATION, etc.
     *
     * @param array $options
     * @return string
     */
    public function compileTableOptions(array $options): string
    {
        // SQLite doesn't support table options like MySQL
        return '';
    }

    /**
     * Get the database driver name.
     *
     * @return string
     */
    public function getDriverName(): string
    {
        return 'sqlite';
    }

    /**
     * Helper method to compile nullable and default value options.
     *
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    protected function compileNullableAndDefault(bool $nullable, $default): string
    {
        $options = $nullable ? '' : ' NOT NULL';

        if ($default !== null) {
            if (is_string($default) && strtoupper($default) !== 'CURRENT_TIMESTAMP') {
                $defaultValue = "'{$default}'";
            } else {
                $defaultValue = $default;
            }
            $options .= " DEFAULT {$defaultValue}";
        }

        return $options;
    }
}
