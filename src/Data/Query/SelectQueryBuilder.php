<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * Class SelectQueryBuilder
 *
 * This class is responsible for building SQL SELECT queries in a programmatic and fluent way.
 * It implements the `QueryBuilderInterface` interface and provides methods to specify the SELECT fields,
 * tables, conditions, joins, ordering, grouping, and other SQL clauses.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class SelectQueryBuilder implements QueryBuilderInterface
{
    /** @var array $fields The fields to select in the query */
    private array $fields = [];

    /** @var array $conditions The conditions for the WHERE clause */
    private array $conditions = [];

    /** @var array $order The fields and directions for the ORDER BY clause */
    private array $order = [];

    /** @var array $from The tables to select from */
    private array $from = [];

    /** @var array $groupBy The fields for the GROUP BY clause */
    private array $groupBy = [];

    /** @var array $having The conditions for the HAVING clause */
    private array $having = [];

    /** @var int|null $start The offset for the LIMIT clause */
    private ?int $start = null;

    /** @var int|null $limit The number of records to limit the results to */
    private ?int $limit = null;

    /** @var bool $distinct Whether to select distinct records */
    private bool $distinct = false;

    /** @var array $join The JOIN clauses */
    private array $join = [];

    /**
     * Constructor for the SelectQueryBuilder class.
     *
     * @param array $select The fields to select.
     */
    public function __construct(array $select)
    {
        $this->fields = $select;
    }

    /**
     * Adds fields to the SELECT clause.
     *
     * @param string ...$select One or more fields to select.
     * @return self Returns the instance for method chaining.
     */
    public function select(string ...$select): self
    {
        foreach ($select as $arg) {
            $this->fields[] = $arg;
        }

        return $this;
    }

    /**
     * Builds and returns the SQL SELECT query as a string.
     *
     * @return string The SQL SELECT query.
     * @throws \LogicException If no table is specified in the FROM clause.
     */
    public function build(): string
    {
        if ($this->from === []) {
            throw new \LogicException('No table specified');
        }

        $sql = 'SELECT '
            . ($this->distinct ? 'DISTINCT ' : '')
            . implode(', ', $this->fields)
            . ' FROM ' . implode(', ', $this->from)
            . ($this->join === [] ? '' : ' ' . implode(' ', $this->join))
            . ($this->conditions === [] ? '' : ' WHERE ' . implode(' ', $this->conditions))
            . ($this->groupBy === [] ? '' : ' GROUP BY ' . implode(', ', $this->groupBy))
            . ($this->having === [] ? '' : ' HAVING ' . implode(' AND ', $this->having))
            . ($this->order === [] ? '' : ' ORDER BY ' . implode(', ', $this->order));

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->start !== null) {
            $sql .= ' OFFSET ' . $this->start;
        }

        return trim($sql);
    }

    /**
     * Adds conditions to the WHERE clause.
     *
     * @param string ...$where One or more conditions.
     * @return self Returns the instance for method chaining.
     */
    public function where(string ...$where): self
    {
        foreach ($where as $arg) {
            if (!empty($this->conditions)) {
                $this->conditions[] = 'AND';
            }
            $this->conditions[] = $arg;
        }

        return $this;
    }

    /**
     * Adds conditions to the WHERE clause with AND logic.
     *
     * @param string ...$condition One or more conditions.
     * @return self Returns the instance for method chaining.
     */
    public function andWhere(string ...$condition): self
    {
        foreach ($condition as $arg) {
            if (!empty($this->conditions)) {
                $this->conditions[] = 'AND';
            }
            $this->conditions[] = $arg;
        }
        return $this;
    }

    /**
     * Adds conditions to the WHERE clause with OR logic.
     *
     * @param string ...$condition One or more conditions.
     * @return self Returns the instance for method chaining.
     */
    public function orWhere(string ...$condition): self
    {
        foreach ($condition as $arg) {
            if (!empty($this->conditions)) {
                $this->conditions[] = 'OR';
            }
            $this->conditions[] = $arg;
        }
        return $this;
    }

    /**
     * Specifies the table(s) to select from, optionally with an alias.
     *
     * @param string      $table The name of the table.
     * @param string|null $alias Optional alias for the table.
     * @return self Returns the instance for method chaining.
     */
    public function from(string $table, ?string $alias = null): self
    {
        $this->from[] = $alias === null ? $table : "$table AS $alias";
        return $this;
    }

    /**
     * Sets the LIMIT clause for the query.
     *
     * @param int $limit The maximum number of records to return.
     * @return self Returns the instance for method chaining.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the OFFSET clause for the query.
     *
     * @param int $offset The offset to start returning records from.
     * @return self Returns the instance for method chaining.
     */
    public function offset(int $offset): self
    {
        $this->start = $offset;
        return $this;
    }

    /**
     * Sets the LIMIT clause with an offset for pagination.
     *
     * @param int $start The offset to start returning records from.
     * @param int $limit The maximum number of records to return.
     * @return self Returns the instance for method chaining.
     */
    public function paginate(int $start, int $limit): self
    {
        $this->start = $start;
        $this->limit = $limit;
        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param string $sort  The field to sort by.
     * @param string $order The sort direction ('ASC' or 'DESC'). Default is 'ASC'.
     * @return self Returns the instance for method chaining.
     */
    public function orderBy(string $sort, string $order = 'ASC'): self
    {
        $this->order[] = "$sort $order";
        return $this;
    }

    /**
     * Adds INNER JOIN clauses to the query.
     *
     * @param string ...$join One or more INNER JOIN clauses.
     * @return self Returns the instance for method chaining.
     */
    public function innerJoin(string ...$join): self
    {
        foreach ($join as $arg) {
            $this->join[] = "INNER JOIN $arg";
        }
        return $this;
    }

    /**
     * Adds LEFT JOIN clauses to the query.
     *
     * @param string ...$join One or more LEFT JOIN clauses.
     * @return self Returns the instance for method chaining.
     */
    public function leftJoin(string ...$join): self
    {
        foreach ($join as $arg) {
            $this->join[] = "LEFT JOIN $arg";
        }

        return $this;
    }

    /**
     * Adds RIGHT JOIN clauses to the query.
     *
     * @param string ...$join One or more RIGHT JOIN clauses.
     * @return self Returns the instance for method chaining.
     */
    public function rightJoin(string ...$join): self
    {
        foreach ($join as $arg) {
            $this->join[] = "RIGHT JOIN $arg";
        }

        return $this;
    }

    /**
     * Sets the query to select distinct records.
     *
     * @return self Returns the instance for method chaining.
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Adds GROUP BY clauses to the query.
     *
     * @param string ...$groupBy One or more fields to group by.
     * @return self Returns the instance for method chaining.
     */
    public function groupBy(string ...$groupBy): self
    {
        foreach ($groupBy as $arg) {
            $this->groupBy[] = $arg;
        }

        return $this;
    }

    /**
     * Adds HAVING clauses to the query.
     *
     * @param string ...$having One or more conditions for the HAVING clause.
     * @return self Returns the instance for method chaining.
     */
    public function having(string ...$having): self
    {
        foreach ($having as $arg) {
            $this->having[] = $arg;
        }

        return $this;
    }

    /**
     * Get the FROM tables.
     *
     * @return array
     */
    public function getFrom(): array
    {
        return $this->from;
    }

    /**
     * Add a subquery to the WHERE clause using EXISTS.
     *
     * @param SelectQueryBuilder $subquery The subquery builder
     * @param bool $not Whether to use NOT EXISTS
     * @return self
     */
    public function whereExists(SelectQueryBuilder $subquery, bool $not = false): self
    {
        $operator = $not ? 'NOT EXISTS' : 'EXISTS';
        $sql = $operator . ' (' . $subquery->build() . ')';

        if (empty($this->conditions)) {
            $this->conditions[] = $sql;
        } else {
            $this->conditions[] = 'AND';
            $this->conditions[] = $sql;
        }

        return $this;
    }

    /**
     * Add a subquery to the WHERE clause using NOT EXISTS.
     *
     * @param SelectQueryBuilder $subquery The subquery builder
     * @return self
     */
    public function whereNotExists(SelectQueryBuilder $subquery): self
    {
        return $this->whereExists($subquery, true);
    }

    /**
     * Add a subquery to the WHERE clause using IN.
     *
     * @param string $column The column to compare
     * @param SelectQueryBuilder $subquery The subquery builder
     * @param bool $not Whether to use NOT IN
     * @return self
     */
    public function whereInSubquery(string $column, SelectQueryBuilder $subquery, bool $not = false): self
    {
        $operator = $not ? 'NOT IN' : 'IN';
        $sql = "$column $operator (" . $subquery->build() . ")";

        if (empty($this->conditions)) {
            $this->conditions[] = $sql;
        } else {
            $this->conditions[] = 'AND';
            $this->conditions[] = $sql;
        }

        return $this;
    }

    /**
     * Add a subquery to the WHERE clause using NOT IN.
     *
     * @param string $column The column to compare
     * @param SelectQueryBuilder $subquery The subquery builder
     * @return self
     */
    public function whereNotInSubquery(string $column, SelectQueryBuilder $subquery): self
    {
        return $this->whereInSubquery($column, $subquery, true);
    }

    /**
     * Use a subquery in the FROM clause.
     *
     * @param SelectQueryBuilder $subquery The subquery builder
     * @param string $alias The alias for the subquery
     * @return self
     */
    public function fromSubquery(SelectQueryBuilder $subquery, string $alias): self
    {
        $sql = '(' . $subquery->build() . ') AS ' . $alias;
        $this->from[] = $sql;
        return $this;
    }

    /**
     * Add a scalar subquery to the SELECT clause.
     *
     * @param SelectQueryBuilder $subquery The subquery builder
     * @param string $alias The alias for the subquery result
     * @return self
     */
    public function selectSubquery(SelectQueryBuilder $subquery, string $alias): self
    {
        $sql = '(' . $subquery->build() . ') AS ' . $alias;
        $this->fields[] = $sql;
        return $this;
    }

    /**
     * Join with a subquery.
     *
     * @param SelectQueryBuilder $subquery The subquery builder
     * @param string $alias The alias for the subquery
     * @param string $condition The join condition
     * @param string $type The join type (INNER, LEFT, RIGHT)
     * @return self
     */
    public function joinSubquery(SelectQueryBuilder $subquery, string $alias, string $condition, string $type = 'INNER'): self
    {
        $sql = strtoupper($type) . ' JOIN (' . $subquery->build() . ') AS ' . $alias . ' ON ' . $condition;
        $this->join[] = $sql;
        return $this;
    }
}
