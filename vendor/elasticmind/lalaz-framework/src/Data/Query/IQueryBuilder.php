<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * Interface IQueryBuilder
 *
 * This interface defines the contract for query builder classes within the application.
 * Any class implementing this interface should provide a method to build and return
 * the SQL query as a string. It ensures consistency and standardization in how queries
 * are constructed across different parts of the application.
 *
 * @package Lalaz\Data\Query
 */
interface IQueryBuilder
{
    /**
     * Builds and returns the SQL query as a string.
     *
     * @return string The SQL query.
     */
    public function build(): string;
}
