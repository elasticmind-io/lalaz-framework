<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

use Lalaz\Data\Contracts\QueryBuilderInterface;

/**
 * InsertQueryBuilder - Fluent query builder for INSERT statements
 *
 * Supports:
 * - Single and batch inserts
 * - MySQL ON DUPLICATE KEY UPDATE
 * - PostgreSQL RETURNING clause
 * - Database-agnostic SQL generation
 *
 * @package Lalaz\Data\Query
 */
class InsertQueryBuilder implements QueryBuilderInterface
{
    private string $table = '';
    private array $columns = [];
    private array $values = [];
    private array $onDuplicateKeyUpdate = [];
    private array $returning = [];
    private array $bindings = [];

    /**
     * Set the table to insert into
     *
     * @param string $table
     * @return self
     */
    public function into(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set columns for the insert
     *
     * @param array $columns
     * @return self
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add a single row of values
     *
     * @param array $values Associative array of column => value
     * @return self
     */
    public function values(array $values): self
    {
        // If columns not set yet, extract from first values array
        if (empty($this->columns) && !empty($values)) {
            $this->columns = array_keys($values);
        }

        $this->values[] = $values;
        return $this;
    }

    /**
     * Add multiple rows of values (batch insert)
     *
     * @param array $rows Array of associative arrays
     * @return self
     */
    public function batchValues(array $rows): self
    {
        foreach ($rows as $row) {
            $this->values($row);
        }
        return $this;
    }

    /**
     * Add ON DUPLICATE KEY UPDATE clause (MySQL only)
     *
     * @param array $updates Associative array of column => value or expression
     * @return self
     */
    public function onDuplicateKeyUpdate(array $updates): self
    {
        $this->onDuplicateKeyUpdate = $updates;
        return $this;
    }

    /**
     * Add RETURNING clause (PostgreSQL only)
     *
     * @param string ...$columns
     * @return self
     */
    public function returning(string ...$columns): self
    {
        $this->returning = $columns;
        return $this;
    }

    /**
     * Build the INSERT SQL query
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function build(): string
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Table name is required for INSERT query');
        }

        if (empty($this->values)) {
            throw new \InvalidArgumentException('At least one row of values is required for INSERT query');
        }

        $sql = "INSERT INTO {$this->table}";

        // Add columns
        if (!empty($this->columns)) {
            $columnList = implode(', ', $this->columns);
            $sql .= " ({$columnList})";
        }

        // Add values
        $sql .= " VALUES";

        $this->bindings = [];
        $valueSets = [];
        $multipleRows = count($this->values) > 1;

        foreach ($this->values as $index => $row) {
            $placeholders = [];
            foreach ($this->columns as $column) {
                $placeholderName = $multipleRows ? "{$column}_{$index}" : $column;
                $placeholders[] = ':' . $placeholderName;
                $this->bindings[$placeholderName] = $row[$column] ?? null;
            }
            $valueSets[] = '(' . implode(', ', $placeholders) . ')';
        }

        $sql .= ' ' . implode(', ', $valueSets);

        // Add ON DUPLICATE KEY UPDATE (MySQL)
        if (!empty($this->onDuplicateKeyUpdate)) {
            $updates = [];
            foreach ($this->onDuplicateKeyUpdate as $column => $value) {
                if (is_string($value) && strpos($value, 'VALUES(') !== false) {
                    // Raw expression like VALUES(column_name)
                    $updates[] = "{$column} = {$value}";
                } else {
                    // Parameterized value
                    $updates[] = "{$column} = :update_{$column}";
                    $this->bindings["update_{$column}"] = $value;
                }
            }
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
        }

        // Add RETURNING clause (PostgreSQL)
        if (!empty($this->returning)) {
            $sql .= ' RETURNING ' . implode(', ', $this->returning);
        }

        return $sql;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the values (all rows)
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Get ON DUPLICATE KEY UPDATE data
     *
     * @return array
     */
    public function getOnDuplicateKeyUpdate(): array
    {
        return $this->onDuplicateKeyUpdate;
    }

    /**
     * Get RETURNING columns
     *
     * @return array
     */
    public function getReturning(): array
    {
        return $this->returning;
    }

    /**
     * Return the flattened bindings for the generated statement.
     *
     * @return array<string, mixed>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
