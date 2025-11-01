<?php declare(strict_types=1);

namespace Lalaz\Data\Schema;

use Lalaz\Data\Schema\Contracts\SchemaGrammarInterface;

/**
 * Class Blueprint
 *
 * This class provides a blueprint for defining database table schemas.
 * It allows you to specify columns, their data types, indexes, foreign keys,
 * and other constraints for creating or modifying tables in a database schema migration.
 *
 * The Blueprint now stores column definitions abstractly and uses a Grammar
 * to compile them into database-specific SQL.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Blueprint
{
    /** @var string $table The name of the table being defined */
    protected string $table;

    /** @var array $columnDefinitions Abstract column definitions (type, params) */
    protected array $columnDefinitions = [];

    /** @var array $indexDefinitions Abstract index definitions */
    protected array $indexDefinitions = [];

    /** @var array $foreignKeyDefinitions Abstract foreign key definitions */
    protected array $foreignKeyDefinitions = [];

    /** @var array $tableOptions An array of table-level options */
    protected array $tableOptions = [];

    /** @var SchemaGrammarInterface|null $grammar The grammar used to compile SQL */
    protected ?SchemaGrammarInterface $grammar = null;

    /**
     * Constructor for the Blueprint class.
     *
     * @param string $table The name of the table to define.
     * @param SchemaGrammarInterface|null $grammar Optional grammar for compilation.
     */
    public function __construct(string $table, ?SchemaGrammarInterface $grammar = null)
    {
        $this->table = $table;
        $this->grammar = $grammar;
    }

    /**
     * Set the grammar used for compiling SQL.
     *
     * @param SchemaGrammarInterface $grammar
     * @return void
     */
    public function setGrammar(SchemaGrammarInterface $grammar): void
    {
        $this->grammar = $grammar;
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
        $this->columnDefinitions[] = [
            'type' => 'increments',
            'column' => $column,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'bigInteger',
            'column' => $column,
            'unsigned' => $unsigned,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'integer',
            'column' => $column,
            'unsigned' => $unsigned,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'smallInteger',
            'column' => $column,
            'unsigned' => $unsigned,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'tinyInteger',
            'column' => $column,
            'unsigned' => $unsigned,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'string',
            'column' => $column,
            'length' => $length,
            'nullable' => $nullable,
            'default' => $default,
            'charset' => $charset,
            'collation' => $collation,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'text',
            'column' => $column,
            'nullable' => $nullable,
            'charset' => $charset,
            'collation' => $collation,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'datetime',
            'column' => $column,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'date',
            'column' => $column,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'time',
            'column' => $column,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'timestamp',
            'column' => $column,
            'nullable' => $nullable,
            'default' => $default,
            'onUpdateCurrentTimestamp' => $onUpdateCurrentTimestamp,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'boolean',
            'column' => $column,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'float',
            'column' => $column,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'double',
            'column' => $column,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'decimal',
            'column' => $column,
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'binary',
            'column' => $column,
            'length' => $length,
            'nullable' => $nullable,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'enum',
            'column' => $column,
            'allowed' => $allowed,
            'nullable' => $nullable,
            'default' => $default,
        ];
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
        $this->columnDefinitions[] = [
            'type' => 'json',
            'column' => $column,
            'nullable' => $nullable,
        ];
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
        $this->columnDefinitions[] = ['type' => 'timestamps'];
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
        $this->indexDefinitions[] = [
            'type' => 'primary',
            'columns' => $columns,
        ];
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
        $this->indexDefinitions[] = [
            'type' => 'unique',
            'name' => $name,
            'columns' => $columns,
        ];
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
        $this->indexDefinitions[] = [
            'type' => 'index',
            'name' => $name,
            'columns' => $columns,
        ];
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
        $this->foreignKeyDefinitions[] = [
            'type' => 'foreign',
            'name' => $constraintName,
            'column' => $column,
            'referencedTable' => $referencedTable,
            'referencedColumn' => $referencedColumn,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate,
        ];
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
        $this->indexDefinitions[] = [
            'type' => 'check',
            'name' => $name,
            'expression' => $expression,
        ];
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

    // Getter Methods for Grammar

    /**
     * Get all column definitions (abstract format).
     *
     * @return array
     */
    public function getColumnDefinitions(): array
    {
        return $this->columnDefinitions;
    }

    /**
     * Get compiled columns using the grammar.
     *
     * @return array
     */
    public function getCompiledColumns(): array
    {
        if (!$this->grammar) {
            throw new \RuntimeException('Grammar not set for Blueprint');
        }

        $compiled = [];

        foreach ($this->columnDefinitions as $definition) {
            $type = $definition['type'];

            switch ($type) {
                case 'increments':
                    $compiled[] = $this->grammar->compileIncrementsColumn($definition['column']);
                    break;

                case 'bigInteger':
                    $compiled[] = $this->grammar->compileBigIntegerColumn(
                        $definition['column'],
                        $definition['unsigned'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'integer':
                    $compiled[] = $this->grammar->compileIntegerColumn(
                        $definition['column'],
                        $definition['unsigned'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'smallInteger':
                    $compiled[] = $this->grammar->compileSmallIntegerColumn(
                        $definition['column'],
                        $definition['unsigned'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'tinyInteger':
                    $compiled[] = $this->grammar->compileTinyIntegerColumn(
                        $definition['column'],
                        $definition['unsigned'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'string':
                    $compiled[] = $this->grammar->compileStringColumn(
                        $definition['column'],
                        $definition['length'],
                        $definition['nullable'],
                        $definition['default'],
                        $definition['charset'] ?? null,
                        $definition['collation'] ?? null
                    );
                    break;

                case 'text':
                    $compiled[] = $this->grammar->compileTextColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['charset'] ?? null,
                        $definition['collation'] ?? null
                    );
                    break;

                case 'datetime':
                    $compiled[] = $this->grammar->compileDatetimeColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'date':
                    $compiled[] = $this->grammar->compileDateColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'time':
                    $compiled[] = $this->grammar->compileTimeColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'timestamp':
                    $compiled[] = $this->grammar->compileTimestampColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['default'],
                        $definition['onUpdateCurrentTimestamp'] ?? false
                    );
                    break;

                case 'boolean':
                    $compiled[] = $this->grammar->compileBooleanColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'float':
                    $compiled[] = $this->grammar->compileFloatColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'double':
                    $compiled[] = $this->grammar->compileDoubleColumn(
                        $definition['column'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'decimal':
                    $compiled[] = $this->grammar->compileDecimalColumn(
                        $definition['column'],
                        $definition['precision'],
                        $definition['scale'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'binary':
                    $compiled[] = $this->grammar->compileBinaryColumn(
                        $definition['column'],
                        $definition['length'],
                        $definition['nullable']
                    );
                    break;

                case 'enum':
                    $compiled[] = $this->grammar->compileEnumColumn(
                        $definition['column'],
                        $definition['allowed'],
                        $definition['nullable'],
                        $definition['default']
                    );
                    break;

                case 'json':
                    $compiled[] = $this->grammar->compileJsonColumn(
                        $definition['column'],
                        $definition['nullable']
                    );
                    break;

                case 'timestamps':
                    $compiled = array_merge($compiled, $this->grammar->compileTimestamps());
                    break;
            }
        }

        return $compiled;
    }

    /**
     * Get compiled indexes using the grammar.
     *
     * @return array
     */
    public function getCompiledIndexes(): array
    {
        if (!$this->grammar) {
            throw new \RuntimeException('Grammar not set for Blueprint');
        }

        $compiled = [];

        foreach ($this->indexDefinitions as $definition) {
            $type = $definition['type'];

            switch ($type) {
                case 'primary':
                    $compiled[] = $this->grammar->compilePrimaryKey($definition['columns']);
                    break;

                case 'unique':
                    $compiled[] = $this->grammar->compileUnique($definition['name'], $definition['columns']);
                    break;

                case 'index':
                    $indexSql = $this->grammar->compileIndex($definition['name'], $definition['columns']);
                    if (!empty($indexSql)) {
                        $compiled[] = $indexSql;
                    }
                    break;

                case 'check':
                    $compiled[] = "CONSTRAINT `{$definition['name']}` CHECK ({$definition['expression']})";
                    break;
            }
        }

        return $compiled;
    }

    /**
     * Get compiled foreign keys using the grammar.
     *
     * @return array
     */
    public function getCompiledForeignKeys(): array
    {
        if (!$this->grammar) {
            throw new \RuntimeException('Grammar not set for Blueprint');
        }

        $compiled = [];

        foreach ($this->foreignKeyDefinitions as $definition) {
            $compiled[] = $this->grammar->compileForeignKey(
                $definition['name'],
                $definition['column'],
                $definition['referencedTable'],
                $definition['referencedColumn'],
                $definition['onDelete'],
                $definition['onUpdate']
            );
        }

        return $compiled;
    }

    /**
     * Get table options.
     *
     * @return array
     */
    public function getTableOptions(): array
    {
        return $this->tableOptions;
    }

    /**
     * Builds and returns the full CREATE TABLE SQL statement using the grammar.
     *
     * @return string The full CREATE TABLE SQL statement.
     */
    public function toSql(): string
    {
        if (!$this->grammar) {
            throw new \RuntimeException('Grammar not set for Blueprint. Call setGrammar() first.');
        }

        return $this->grammar->compileCreate($this);
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
