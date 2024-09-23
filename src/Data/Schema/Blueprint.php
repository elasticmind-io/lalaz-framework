<?php declare(strict_types=1);

namespace Lalaz\Data\Schema;

/**
 * Class Blueprint
 *
 * This class provides a blueprint for defining database table schemas.
 * It allows you to specify columns, their data types, indexes, foreign keys,
 * and other constraints for creating or modifying tables in a database schema migration.
 *
 * @package Lalaz\Data\Schema
 */
class Blueprint
{
    /** @var string $table The name of the table being defined */
    protected string $table;

    /** @var array $columns An array of column definitions */
    protected array $columns = [];

    /** @var array $indexes An array of index definitions */
    protected array $indexes = [];

    /** @var array $foreignKeys An array of foreign key definitions */
    protected array $foreignKeys = [];

    /** @var array $tableOptions An array of table-level options */
    protected array $tableOptions = [];

    /**
     * Constructor for the Blueprint class.
     *
     * @param string $table The name of the table to define.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // Column Definitions

    /**
     * Adds an auto-incrementing integer column as the primary key.
     *
     * @param string $column The name of the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function increments(string $column): self
    {
        $this->columns[] = "$column INT AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    /**
     * Adds a big integer (BIGINT) column.
     *
     * @param string $column   The name of the column.
     * @param bool   $unsigned Whether the column is unsigned. Default is false.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function bigInteger(string $column, bool $unsigned = false, bool $nullable = false, $default = null): self
    {
        $definition = "$column BIGINT" . ($unsigned ? " UNSIGNED" : "") . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds an integer (INT) column.
     *
     * @param string $column   The name of the column.
     * @param bool   $unsigned Whether the column is unsigned. Default is false.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function integer(string $column, bool $unsigned = false, bool $nullable = false, $default = null): self
    {
        $definition = "$column INT" . ($unsigned ? " UNSIGNED" : "") . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a small integer (SMALLINT) column.
     *
     * @param string $column   The name of the column.
     * @param bool   $unsigned Whether the column is unsigned. Default is false.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function smallInteger(string $column, bool $unsigned = false, bool $nullable = false, $default = null): self
    {
        $definition = "$column SMALLINT" . ($unsigned ? " UNSIGNED" : "") . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a tiny integer (TINYINT) column.
     *
     * @param string $column   The name of the column.
     * @param bool   $unsigned Whether the column is unsigned. Default is false.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function tinyInteger(string $column, bool $unsigned = false, bool $nullable = false, $default = null): self
    {
        $definition = "$column TINYINT" . ($unsigned ? " UNSIGNED" : "") . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a VARCHAR column with a specified length.
     *
     * @param string $column   The name of the column.
     * @param int    $length   The length of the VARCHAR column. Default is 255.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @param string|null $charset The character set for the column.
     * @param string|null $collation The collation for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function string(string $column, int $length = 255, bool $nullable = false, $default = null, ?string $charset = null, ?string $collation = null): self
    {
        $definition = "$column VARCHAR($length)" . $this->buildColumnOptions($nullable, $default, $charset, $collation);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a TEXT column.
     *
     * @param string      $column    The name of the column.
     * @param bool        $nullable  Whether the column allows NULL values. Default is false.
     * @param string|null $charset   The character set for the column.
     * @param string|null $collation The collation for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function text(string $column, bool $nullable = false, ?string $charset = null, ?string $collation = null): self
    {
        $definition = "$column TEXT" . $this->buildColumnOptions($nullable, null, $charset, $collation);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a DATETIME column.
     *
     * @param string $column   The name of the column.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function datetime(string $column, bool $nullable = false, $default = null): self
    {
        $definition = "$column DATETIME" . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a DATE column.
     *
     * @param string $column   The name of the column.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function date(string $column, bool $nullable = false, $default = null): self
    {
        $definition = "$column DATE" . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a TIME column.
     *
     * @param string $column   The name of the column.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function time(string $column, bool $nullable = false, $default = null): self
    {
        $definition = "$column TIME" . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a TIMESTAMP column.
     *
     * @param string $column    The name of the column.
     * @param bool   $nullable  Whether the column allows NULL values. Default is false.
     * @param mixed  $default   The default value for the column.
     * @param bool   $onUpdateCurrentTimestamp Whether to update the timestamp on row modification.
     * @return $this The Blueprint instance for method chaining.
     */
    public function timestamp(string $column, bool $nullable = false, $default = null, bool $onUpdateCurrentTimestamp = false): self
    {
        $definition = "$column TIMESTAMP" . $this->buildColumnOptions($nullable, $default);
        if ($onUpdateCurrentTimestamp) {
            $definition .= " ON UPDATE CURRENT_TIMESTAMP";
        }
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a BOOLEAN column.
     *
     * @param string $column    The name of the column.
     * @param bool   $nullable  Whether the column allows NULL values. Default is false.
     * @param bool   $default   The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function boolean(string $column, bool $nullable = false, bool $default = false): self
    {
        $defaultValue = $default ? '1' : '0';
        $definition = "$column TINYINT(1)" . $this->buildColumnOptions($nullable, $defaultValue);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a FLOAT column.
     *
     * @param string $column    The name of the column.
     * @param bool   $nullable  Whether the column allows NULL values. Default is false.
     * @param mixed  $default   The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function float(string $column, bool $nullable = false, $default = null): self
    {
        $definition = "$column FLOAT" . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a DOUBLE column.
     *
     * @param string $column    The name of the column.
     * @param bool   $nullable  Whether the column allows NULL values. Default is false.
     * @param mixed  $default   The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function double(string $column, bool $nullable = false, $default = null): self
    {
        $definition = "$column DOUBLE" . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a DECIMAL column.
     *
     * @param string $column    The name of the column.
     * @param int    $precision The total number of digits.
     * @param int    $scale     The number of digits after the decimal point.
     * @param bool   $nullable  Whether the column allows NULL values. Default is false.
     * @param mixed  $default   The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function decimal(string $column, int $precision = 8, int $scale = 2, bool $nullable = false, $default = null): self
    {
        $definition = "$column DECIMAL($precision, $scale)" . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a BINARY column.
     *
     * @param string $column   The name of the column.
     * @param int    $length   The length of the binary data.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @return $this The Blueprint instance for method chaining.
     */
    public function binary(string $column, int $length = 255, bool $nullable = false): self
    {
        $definition = "$column BINARY($length)" . $this->buildColumnOptions($nullable);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds an ENUM column.
     *
     * @param string $column   The name of the column.
     * @param array  $allowed  The allowed values for the ENUM.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @param mixed  $default  The default value for the column.
     * @return $this The Blueprint instance for method chaining.
     */
    public function enum(string $column, array $allowed, bool $nullable = false, $default = null): self
    {
        $allowedValues = implode("', '", $allowed);
        $definition = "$column ENUM('$allowedValues')" . $this->buildColumnOptions($nullable, $default);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds a JSON column.
     *
     * @param string $column   The name of the column.
     * @param bool   $nullable Whether the column allows NULL values. Default is false.
     * @return $this The Blueprint instance for method chaining.
     */
    public function json(string $column, bool $nullable = false): self
    {
        $definition = "$column JSON" . $this->buildColumnOptions($nullable);
        $this->columns[] = $definition;
        return $this;
    }

    /**
     * Adds created_at and updated_at timestamp columns.
     *
     * - `created_at` defaults to the current timestamp.
     * - `updated_at` defaults to the current timestamp and updates on row modification.
     *
     * @return $this The Blueprint instance for method chaining.
     */
    public function timestamps(): self
    {
        $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    /**
     * Adds a deleted_at column for soft deletes.
     *
     * @return $this The Blueprint instance for method chaining.
     */
    public function softDeletes(): self
    {
        $this->datetime('deleted_at', true);
        return $this;
    }

    // Indexes and Constraints

    /**
     * Adds a PRIMARY KEY constraint to a column or columns.
     *
     * @param string|array $columns The column name or an array of column names.
     * @return $this The Blueprint instance for method chaining.
     */
    public function primary($columns): self
    {
        $columns = (array)$columns;
        $columnsList = implode(', ', $columns);
        $this->indexes[] = "PRIMARY KEY ($columnsList)";
        return $this;
    }

    /**
     * Adds a UNIQUE index to a column or columns.
     *
     * @param string|array $columns The column name or an array of column names.
     * @param string|null  $name    The name of the index. If null, a name is generated.
     * @return $this The Blueprint instance for method chaining.
     */
    public function unique($columns, ?string $name = null): self
    {
        $columns = (array)$columns;
        $name = $name ?? $this->generateIndexName('unique', $columns);
        $columnsList = implode(', ', $columns);
        $this->indexes[] = "UNIQUE KEY `$name` ($columnsList)";
        return $this;
    }

    /**
     * Adds an INDEX to a column or columns.
     *
     * @param string|array $columns The column name or an array of column names.
     * @param string|null  $name    The name of the index. If null, a name is generated.
     * @return $this The Blueprint instance for method chaining.
     */
    public function index($columns, ?string $name = null): self
    {
        $columns = (array)$columns;
        $name = $name ?? $this->generateIndexName('index', $columns);
        $columnsList = implode(', ', $columns);
        $this->indexes[] = "INDEX `$name` ($columnsList)";
        return $this;
    }

    /**
     * Adds a foreign key constraint to a column.
     *
     * @param string $column           The column name.
     * @param string $referencedTable  The referenced table name.
     * @param string $referencedColumn The referenced column name. Default is 'id'.
     * @param string $onDelete         The action on delete. Default is 'CASCADE'.
     * @param string $onUpdate         The action on update. Default is 'CASCADE'.
     * @param string|null $constraintName The name of the foreign key constraint. If null, a name is generated.
     * @return $this The Blueprint instance for method chaining.
     */
    public function foreign(string $column, string $referencedTable, string $referencedColumn = 'id', string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE', ?string $constraintName = null): self
    {
        $constraintName = $constraintName ?? $this->generateForeignKeyName($column);
        $definition = "CONSTRAINT `$constraintName` FOREIGN KEY ($column) REFERENCES $referencedTable($referencedColumn) ON DELETE $onDelete ON UPDATE $onUpdate";
        $this->foreignKeys[] = $definition;
        return $this;
    }

    /**
     * Adds a CHECK constraint to the table.
     *
     * @param string      $expression The check expression.
     * @param string|null $name       The name of the check constraint. If null, a name is generated.
     * @return $this The Blueprint instance for method chaining.
     */
    public function check(string $expression, ?string $name = null): self
    {
        $name = $name ?? $this->generateConstraintName('check', [$expression]);
        $this->indexes[] = "CONSTRAINT `$name` CHECK ($expression)";
        return $this;
    }

    // Table Options

    /**
     * Sets the storage engine for the table.
     *
     * @param string $engine The storage engine (e.g., 'InnoDB', 'MyISAM').
     * @return $this The Blueprint instance for method chaining.
     */
    public function engine(string $engine): self
    {
        $this->tableOptions['engine'] = $engine;
        return $this;
    }

    /**
     * Sets the default character set for the table.
     *
     * @param string $charset The character set (e.g., 'utf8mb4').
     * @return $this The Blueprint instance for method chaining.
     */
    public function charset(string $charset): self
    {
        $this->tableOptions['charset'] = $charset;
        return $this;
    }

    /**
     * Sets the default collation for the table.
     *
     * @param string $collation The collation (e.g., 'utf8mb4_unicode_ci').
     * @return $this The Blueprint instance for method chaining.
     */
    public function collation(string $collation): self
    {
        $this->tableOptions['collation'] = $collation;
        return $this;
    }

    /**
     * Adds a comment to the table.
     *
     * @param string $comment The table comment.
     * @return $this The Blueprint instance for method chaining.
     */
    public function tableComment(string $comment): self
    {
        $this->tableOptions['comment'] = $comment;
        return $this;
    }

    // Helper Methods

    /**
     * Builds the column options for nullable, default values, charset, and collation.
     *
     * @param bool        $nullable  Whether the column allows NULL values.
     * @param mixed       $default   The default value for the column.
     * @param string|null $charset   The character set for the column.
     * @param string|null $collation The collation for the column.
     * @return string The column options as a string.
     */
    private function buildColumnOptions(bool $nullable, $default = null, ?string $charset = null, ?string $collation = null): string
    {
        $options = $nullable ? ' NULL' : ' NOT NULL';
        if ($default !== null) {
            if (is_string($default) && strtoupper($default) !== 'CURRENT_TIMESTAMP') {
                $defaultValue = "'$default'";
            } else {
                $defaultValue = $default;
            }
            $options .= " DEFAULT $defaultValue";
        }
        if ($charset) {
            $options .= " CHARACTER SET $charset";
        }
        if ($collation) {
            $options .= " COLLATE $collation";
        }
        return $options;
    }

    /**
     * Generates an index name based on the type and columns.
     *
     * @param string $type    The type of index (unique, index).
     * @param array  $columns The columns included in the index.
     * @return string The generated index name.
     */
    private function generateIndexName(string $type, array $columns): string
    {
        $table = $this->table;
        $columnsPart = implode('_', $columns);
        return "{$table}_{$columnsPart}_{$type}";
    }

    /**
     * Generates a foreign key constraint name based on the column.
     *
     * @param string $column The column name.
     * @return string The generated foreign key constraint name.
     */
    private function generateForeignKeyName(string $column): string
    {
        $table = $this->table;
        return "{$table}_{$column}_foreign";
    }

    /**
     * Generates a constraint name based on the type and expressions.
     *
     * @param string $type        The type of constraint (e.g., 'check').
     * @param array  $expressions The expressions included in the constraint.
     * @return string The generated constraint name.
     */
    private function generateConstraintName(string $type, array $expressions): string
    {
        $table = $this->table;
        $expressionsPart = substr(md5(implode('_', $expressions)), 0, 8);
        return "{$table}_{$expressionsPart}_{$type}";
    }

    /**
     * Retrieves the full table definition including columns, indexes, and foreign keys.
     *
     * @return string The table definition for use in SQL statements.
     */
    public function getTableDefinition(): string
    {
        $definitions = array_merge($this->columns, $this->indexes, $this->foreignKeys);
        return implode(",\n", $definitions);
    }

    /**
     * Builds and returns the full CREATE TABLE SQL statement.
     *
     * @return string The full CREATE TABLE SQL statement.
     */
    public function toSql(): string
    {
        $tableName = $this->getTableName();
        $definitions = $this->getTableDefinition();
        $options = $this->getTableOptions();
        return "CREATE TABLE $tableName (\n$definitions\n) $options;";
    }

    /**
     * Retrieves the table options as a string.
     *
     * @return string The table options.
     */
    private function getTableOptions(): string
    {
        $options = [];
        if (isset($this->tableOptions['engine'])) {
            $options[] = "ENGINE={$this->tableOptions['engine']}";
        }
        if (isset($this->tableOptions['charset'])) {
            $options[] = "DEFAULT CHARSET={$this->tableOptions['charset']}";
        }
        if (isset($this->tableOptions['collation'])) {
            $options[] = "COLLATE={$this->tableOptions['collation']}";
        }
        if (isset($this->tableOptions['comment'])) {
            $options[] = "COMMENT='{$this->tableOptions['comment']}'";
        }
        return implode(' ', $options);
    }

    /**
     * Gets the name of the table.
     *
     * @return string The table name.
     */
    public function getTableName(): string
    {
        return $this->table;
    }
}
