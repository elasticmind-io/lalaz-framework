<?php declare(strict_types=1);

namespace Lalaz\Data\Schema\Grammars;

use Lalaz\Data\Schema\Blueprint;
use Lalaz\Data\Schema\Contracts\SchemaGrammarInterface;

/**
 * Class MySqlGrammar
 *
 * MySQL-specific implementation of the schema grammar.
 * Generates MySQL-compatible SQL statements for table creation and modification.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class MySqlGrammar implements SchemaGrammarInterface
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

        $tableOptions = $this->compileTableOptions($blueprint->getTableOptions());

        return "CREATE TABLE {$tableName} (\n{$definitionsStr}\n) {$tableOptions};";
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
     *
     * @param string $column
     * @return string
     */
    public function compileIncrementsColumn(string $column): string
    {
        return "{$column} BIGINT AUTO_INCREMENT PRIMARY KEY";
    }

    /**
     * Compile a big integer column definition.
     *
     * @param string $column
     * @param bool $unsigned
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileBigIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} BIGINT";

        if ($unsigned) {
            $definition .= " UNSIGNED";
        }

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        return $definition;
    }

    /**
     * Compile an integer column definition.
     *
     * @param string $column
     * @param bool $unsigned
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} INT";

        if ($unsigned) {
            $definition .= " UNSIGNED";
        }

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        return $definition;
    }

    /**
     * Compile a small integer column definition.
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

        if ($unsigned) {
            $definition .= " UNSIGNED";
        }

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        return $definition;
    }

    /**
     * Compile a tiny integer column definition.
     *
     * @param string $column
     * @param bool $unsigned
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileTinyIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string
    {
        $definition = "{$column} TINYINT";

        if ($unsigned) {
            $definition .= " UNSIGNED";
        }

        $definition .= $this->compileNullableAndDefault($nullable, $default);

        return $definition;
    }

    /**
     * Compile a string/varchar column definition.
     *
     * @param string $column
     * @param int $length
     * @param bool $nullable
     * @param mixed $default
     * @param string|null $charset
     * @param string|null $collation
     * @return string
     */
    public function compileStringColumn(string $column, int $length, bool $nullable, $default, ?string $charset, ?string $collation): string
    {
        $definition = "{$column} VARCHAR({$length})";
        $definition .= $this->compileNullableAndDefault($nullable, $default);

        if ($charset) {
            $definition .= " CHARACTER SET {$charset}";
        }

        if ($collation) {
            $definition .= " COLLATE {$collation}";
        }

        return $definition;
    }

    /**
     * Compile a text column definition.
     *
     * @param string $column
     * @param bool $nullable
     * @param string|null $charset
     * @param string|null $collation
     * @return string
     */
    public function compileTextColumn(string $column, bool $nullable, ?string $charset, ?string $collation): string
    {
        $definition = "{$column} TEXT";
        $definition .= $this->compileNullableAndDefault($nullable, null);

        if ($charset) {
            $definition .= " CHARACTER SET {$charset}";
        }

        if ($collation) {
            $definition .= " COLLATE {$collation}";
        }

        return $definition;
    }

    /**
     * Compile a datetime column definition.
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
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @param bool $onUpdateCurrentTimestamp
     * @return string
     */
    public function compileTimestampColumn(string $column, bool $nullable, $default, bool $onUpdateCurrentTimestamp): string
    {
        $definition = "{$column} TIMESTAMP";
        $definition .= $this->compileNullableAndDefault($nullable, $default);

        if ($onUpdateCurrentTimestamp) {
            $definition .= " ON UPDATE CURRENT_TIMESTAMP";
        }

        return $definition;
    }

    /**
     * Compile a boolean column definition.
     *
     * @param string $column
     * @param bool $nullable
     * @param bool $default
     * @return string
     */
    public function compileBooleanColumn(string $column, bool $nullable, bool $default): string
    {
        $defaultValue = $default ? '1' : '0';
        return "{$column} TINYINT(1)" . $this->compileNullableAndDefault($nullable, $defaultValue);
    }

    /**
     * Compile a float column definition.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileFloatColumn(string $column, bool $nullable, $default): string
    {
        return "{$column} FLOAT" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a double column definition.
     *
     * @param string $column
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileDoubleColumn(string $column, bool $nullable, $default): string
    {
        return "{$column} DOUBLE" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a decimal column definition.
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
        return "{$column} DECIMAL({$precision}, {$scale})" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a binary column definition.
     *
     * @param string $column
     * @param int $length
     * @param bool $nullable
     * @return string
     */
    public function compileBinaryColumn(string $column, int $length, bool $nullable): string
    {
        return "{$column} BINARY({$length})" . $this->compileNullableAndDefault($nullable, null);
    }

    /**
     * Compile an enum column definition.
     *
     * @param string $column
     * @param array $allowed
     * @param bool $nullable
     * @param mixed $default
     * @return string
     */
    public function compileEnumColumn(string $column, array $allowed, bool $nullable, $default): string
    {
        $allowedValues = implode("', '", $allowed);
        return "{$column} ENUM('{$allowedValues}')" . $this->compileNullableAndDefault($nullable, $default);
    }

    /**
     * Compile a JSON column definition.
     *
     * @param string $column
     * @param bool $nullable
     * @return string
     */
    public function compileJsonColumn(string $column, bool $nullable): string
    {
        return "{$column} JSON" . $this->compileNullableAndDefault($nullable, null);
    }

    /**
     * Compile timestamps (created_at and updated_at) columns.
     *
     * @return array
     */
    public function compileTimestamps(): array
    {
        return [
            "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
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
        return "UNIQUE KEY `{$name}` ({$columnsList})";
    }

    /**
     * Compile an index.
     *
     * @param string $name
     * @param array $columns
     * @return string
     */
    public function compileIndex(string $name, array $columns): string
    {
        $columnsList = implode(', ', $columns);
        return "INDEX `{$name}` ({$columnsList})";
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
        return "CONSTRAINT `{$name}` FOREIGN KEY ({$column}) REFERENCES {$referencedTable}({$referencedColumn}) ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
    }

    /**
     * Compile table options (engine, charset, collation, etc.).
     *
     * @param array $options
     * @return string
     */
    public function compileTableOptions(array $options): string
    {
        $compiled = [];

        if (isset($options['engine'])) {
            $compiled[] = "ENGINE={$options['engine']}";
        }

        if (isset($options['charset'])) {
            $compiled[] = "DEFAULT CHARSET={$options['charset']}";
        }

        if (isset($options['collation'])) {
            $compiled[] = "COLLATE={$options['collation']}";
        }

        if (isset($options['comment'])) {
            $compiled[] = "COMMENT='{$options['comment']}'";
        }

        return implode(' ', $compiled);
    }

    /**
     * Get the database driver name.
     *
     * @return string
     */
    public function getDriverName(): string
    {
        return 'mysql';
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
        $options = $nullable ? ' NULL' : ' NOT NULL';

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
