<?php declare(strict_types=1);

namespace Lalaz\Data\Contracts;

use Lalaz\Data\Schema\Blueprint;

/**
 * Interface SchemaGrammarInterface
 *
 * Defines the contract for database-specific schema grammars.
 * Each database driver (MySQL, SQLite, PostgreSQL, etc.) should implement
 * this interface to provide database-specific SQL generation.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
interface SchemaGrammarInterface
{
    /**
     * Compile a CREATE TABLE statement from the blueprint.
     *
     * @param Blueprint $blueprint The table blueprint definition.
     * @return string The compiled CREATE TABLE SQL statement.
     */
    public function compileCreate(Blueprint $blueprint): string;

    /**
     * Compile a DROP TABLE IF EXISTS statement.
     *
     * @param string $table The table name.
     * @return string The compiled DROP TABLE SQL statement.
     */
    public function compileDrop(string $table): string;

    /**
     * Compile an auto-incrementing column definition.
     *
     * @param string $column The column name.
     * @return string The compiled column definition.
     */
    public function compileIncrementsColumn(string $column): string;

    /**
     * Compile a big integer column definition.
     *
     * @param string $column The column name.
     * @param bool $unsigned Whether the column is unsigned.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileBigIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string;

    /**
     * Compile an integer column definition.
     *
     * @param string $column The column name.
     * @param bool $unsigned Whether the column is unsigned.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string;

    /**
     * Compile a small integer column definition.
     *
     * @param string $column The column name.
     * @param bool $unsigned Whether the column is unsigned.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileSmallIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string;

    /**
     * Compile a tiny integer column definition.
     *
     * @param string $column The column name.
     * @param bool $unsigned Whether the column is unsigned.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileTinyIntegerColumn(string $column, bool $unsigned, bool $nullable, $default): string;

    /**
     * Compile a string/varchar column definition.
     *
     * @param string $column The column name.
     * @param int $length The length of the string.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @param string|null $charset The character set.
     * @param string|null $collation The collation.
     * @return string The compiled column definition.
     */
    public function compileStringColumn(string $column, int $length, bool $nullable, $default, ?string $charset, ?string $collation): string;

    /**
     * Compile a text column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param string|null $charset The character set.
     * @param string|null $collation The collation.
     * @return string The compiled column definition.
     */
    public function compileTextColumn(string $column, bool $nullable, ?string $charset, ?string $collation): string;

    /**
     * Compile a datetime column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileDatetimeColumn(string $column, bool $nullable, $default): string;

    /**
     * Compile a date column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileDateColumn(string $column, bool $nullable, $default): string;

    /**
     * Compile a time column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileTimeColumn(string $column, bool $nullable, $default): string;

    /**
     * Compile a timestamp column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @param bool $onUpdateCurrentTimestamp Whether to update on row modification.
     * @return string The compiled column definition.
     */
    public function compileTimestampColumn(string $column, bool $nullable, $default, bool $onUpdateCurrentTimestamp): string;

    /**
     * Compile a boolean column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param bool $default The default value.
     * @return string The compiled column definition.
     */
    public function compileBooleanColumn(string $column, bool $nullable, bool $default): string;

    /**
     * Compile a float column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileFloatColumn(string $column, bool $nullable, $default): string;

    /**
     * Compile a double column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileDoubleColumn(string $column, bool $nullable, $default): string;

    /**
     * Compile a decimal column definition.
     *
     * @param string $column The column name.
     * @param int $precision The total number of digits.
     * @param int $scale The number of decimal places.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileDecimalColumn(string $column, int $precision, int $scale, bool $nullable, $default): string;

    /**
     * Compile a binary column definition.
     *
     * @param string $column The column name.
     * @param int $length The length of the binary data.
     * @param bool $nullable Whether the column allows NULL.
     * @return string The compiled column definition.
     */
    public function compileBinaryColumn(string $column, int $length, bool $nullable): string;

    /**
     * Compile an enum column definition.
     *
     * @param string $column The column name.
     * @param array $allowed The allowed values.
     * @param bool $nullable Whether the column allows NULL.
     * @param mixed $default The default value.
     * @return string The compiled column definition.
     */
    public function compileEnumColumn(string $column, array $allowed, bool $nullable, $default): string;

    /**
     * Compile a JSON column definition.
     *
     * @param string $column The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @return string The compiled column definition.
     */
    public function compileJsonColumn(string $column, bool $nullable): string;

    /**
     * Compile timestamps (created_at and updated_at) columns.
     *
     * @return array Array of compiled column definitions.
     */
    public function compileTimestamps(): array;

    /**
     * Compile a primary key constraint.
     *
     * @param array $columns The column(s) for the primary key.
     * @return string The compiled primary key constraint.
     */
    public function compilePrimaryKey(array $columns): string;

    /**
     * Compile a unique constraint.
     *
     * @param string $name The constraint name.
     * @param array $columns The column(s) for the unique constraint.
     * @return string The compiled unique constraint.
     */
    public function compileUnique(string $name, array $columns): string;

    /**
     * Compile an index.
     *
     * @param string $name The index name.
     * @param array $columns The column(s) for the index.
     * @return string The compiled index.
     */
    public function compileIndex(string $name, array $columns): string;

    /**
     * Compile a foreign key constraint.
     *
     * @param string $name The constraint name.
     * @param string $column The local column.
     * @param string $referencedTable The referenced table.
     * @param string $referencedColumn The referenced column.
     * @param string $onDelete The ON DELETE action.
     * @param string $onUpdate The ON UPDATE action.
     * @return string The compiled foreign key constraint.
     */
    public function compileForeignKey(string $name, string $column, string $referencedTable, string $referencedColumn, string $onDelete, string $onUpdate): string;

    /**
     * Compile table options (engine, charset, collation, etc.).
     *
     * @param array $options The table options.
     * @return string The compiled table options.
     */
    public function compileTableOptions(array $options): string;

    /**
     * Get the database driver name.
     *
     * @return string The driver name (e.g., 'mysql', 'sqlite').
     */
    public function getDriverName(): string;
}
