<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * Class DeleteQueryBuilder
 *
 * This class is responsible for building SQL DELETE queries in a programmatic way.
 * It implements the `QueryBuilderInterface` interface and provides methods to specify the table
 * and conditions for the DELETE operation.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class DeleteQueryBuilder implements QueryBuilderInterface
{
    /** @var array $conditions The conditions for the WHERE clause */
    private array $conditions = [];

    /** @var array $from The tables from which to delete records */
    private array $from = [];

    /**
     * Builds and returns the SQL DELETE query as a string.
     *
     * @return string The SQL DELETE query.
     * @throws \LogicException If no table is specified for deletion.
     */
    public function build(): string
    {
        if ($this->from === []) {
            throw new \LogicException('No table specified');
        }

        return 'DELETE FROM '
            . implode(', ', $this->from)
            . ($this->conditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->conditions));
    }

    /**
     * Specifies the table(s) from which to delete records.
     *
     * @param string $table The name of the table.
     * @return self Returns the instance for method chaining.
     */
    public function from(string $table): self
    {
        $this->from[] = $table;
        return $this;
    }

    /**
     * Adds conditions to the WHERE clause of the DELETE query.
     *
     * @param string ...$where One or more conditions as strings.
     * @return self Returns the instance for method chaining.
     */
    public function where(string ...$where): self
    {
        foreach ($where as $arg) {
            $this->conditions[] = $arg;
        }

        return $this;
    }
}
