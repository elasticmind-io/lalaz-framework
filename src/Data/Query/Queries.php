<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * Class Queries
 *
 * This final class provides static factory methods to create instances of query builder classes,
 * such as `SelectQueryBuilder` and `DeleteQueryBuilder`. It serves as a convenient entry point
 * for building SQL queries in a fluent and consistent manner throughout the application.
 *
 * @package Lalaz\Data\Query
 */
final class Queries
{
    /**
     * Creates and returns a new instance of the `SelectQueryBuilder` class.
     *
     * @param string ...$select The columns to select in the SQL query.
     * @return SelectQueryBuilder An instance of the `SelectQueryBuilder` initialized with the provided columns.
     */
    public static function select(string ...$select): SelectQueryBuilder
    {
        return new SelectQueryBuilder($select);
    }

    /**
     * Creates and returns a new instance of the `DeleteQueryBuilder` class.
     *
     * @return DeleteQueryBuilder An instance of the `DeleteQueryBuilder`.
     */
    public static function delete(): DeleteQueryBuilder
    {
        return new DeleteQueryBuilder();
    }
}
