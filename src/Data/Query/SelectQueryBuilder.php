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
            . ($this->conditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->conditions))
            . ($this->groupBy === [] ? '' : ' GROUP BY ' . implode(', ', $this->groupBy))
            . ($this->having === [] ? '' : ' HAVING ' . implode(' AND ', $this->having))
            . ($this->order === [] ? '' : ' ORDER BY ' . implode(', ', $this->order));

        if ($this->limit !== null) {
            $sql .= ' LIMIT ';
            if ($this->start !== null) {
                $sql .= $this->start . ', ';
            }
            $sql .= $this->limit;
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
                $this->conditions[] = "OR $arg";
            } else {
                $this->conditions[] = $arg;
            }
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
}
