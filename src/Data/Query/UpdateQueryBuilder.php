<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * UpdateQueryBuilder - Fluent query builder for UPDATE statements
 *
 * Supports:
 * - Single and multiple column updates
 * - WHERE conditions
 * - JOINs for complex updates
 * - PostgreSQL RETURNING clause
 * - Database-agnostic SQL generation
 *
 * @package Lalaz\Data\Query
 */
class UpdateQueryBuilder implements QueryBuilderInterface
{
    private string $table = '';
    private array $set = [];
    private array $where = [];
    private array $joins = [];
    private array $returning = [];

    /**
     * Set the table to update
     *
     * @param string $table
     * @return self
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set column value(s) to update
     *
     * @param string|array $column Column name or associative array of column => value
     * @param mixed $value Value to set (ignored if $column is array)
     * @return self
     */
    public function set($column, $value = null): self
    {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->set[$col] = $val;
            }
        } else {
            $this->set[$column] = $value;
        }
        return $this;
    }

    /**
     * Add a WHERE condition
     *
     * @param string $condition
     * @return self
     */
    public function where(string $condition): self
    {
        if (empty($this->where)) {
            $this->where[] = $condition;
        } else {
            $this->where[] = 'AND';
            $this->where[] = $condition;
        }
        return $this;
    }

    /**
     * Add an AND WHERE condition
     *
     * @param string $condition
     * @return self
     */
    public function andWhere(string $condition): self
    {
        if (empty($this->where)) {
            $this->where[] = $condition;
        } else {
            $this->where[] = 'AND';
            $this->where[] = $condition;
        }
        return $this;
    }

    /**
     * Add an OR WHERE condition
     *
     * @param string $condition
     * @return self
     */
    public function orWhere(string $condition): self
    {
        if (empty($this->where)) {
            $this->where[] = $condition;
        } else {
            $this->where[] = 'OR';
            $this->where[] = $condition;
        }
        return $this;
    }

    /**
     * Add a JOIN clause
     *
     * @param string $table Table to join
     * @param string $condition Join condition
     * @param string $type Join type (INNER, LEFT, RIGHT)
     * @return self
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => $table,
            'condition' => $condition
        ];
        return $this;
    }

    /**
     * Add an INNER JOIN
     *
     * @param string $table
     * @param string $condition
     * @return self
     */
    public function innerJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'INNER');
    }

    /**
     * Add a LEFT JOIN
     *
     * @param string $table
     * @param string $condition
     * @return self
     */
    public function leftJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN
     *
     * @param string $table
     * @param string $condition
     * @return self
     */
    public function rightJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'RIGHT');
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
     * Build the UPDATE SQL query
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function build(): string
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Table name is required for UPDATE query');
        }

        if (empty($this->set)) {
            throw new \InvalidArgumentException('At least one column must be set for UPDATE query');
        }

        $sql = "UPDATE {$this->table}";

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['condition']}";
        }

        // Add SET clause
        $setParts = [];
        foreach ($this->set as $column => $value) {
            if (is_string($value) && (
                strpos($value, '(') !== false ||
                strpos($value, '+') !== false ||
                strpos($value, '-') !== false ||
                strpos($value, '*') !== false ||
                strpos($value, '/') !== false
            )) {
                // Raw expression (e.g., "column + 1", "UPPER(column)")
                $setParts[] = "{$column} = {$value}";
            } else {
                // Parameterized value
                $setParts[] = "{$column} = :{$column}";
            }
        }
        $sql .= ' SET ' . implode(', ', $setParts);

        // Add WHERE clause
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
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
     * Get the SET data
     *
     * @return array
     */
    public function getSet(): array
    {
        return $this->set;
    }

    /**
     * Get the WHERE conditions
     *
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Get the JOINs
     *
     * @return array
     */
    public function getJoins(): array
    {
        return $this->joins;
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
}
