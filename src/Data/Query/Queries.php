<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * Class Queries
 *
 * This final class provides static factory methods to create instances of query builder classes,
 * such as `SelectQueryBuilder`, `DeleteQueryBuilder`, `InsertQueryBuilder`, and `UpdateQueryBuilder`.
 * It serves as a convenient entry point for building SQL queries in a fluent and consistent manner
 * throughout the application.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
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

    /**
     * Creates and returns a new instance of the `InsertQueryBuilder` class.
     *
     * @param string $table The table to insert into.
     * @return InsertQueryBuilder An instance of the `InsertQueryBuilder`.
     */
    public static function insert(string $table): InsertQueryBuilder
    {
        return (new InsertQueryBuilder())->into($table);
    }

    /**
     * Creates and returns a new instance of the `UpdateQueryBuilder` class.
     *
     * @param string $table The table to update.
     * @return UpdateQueryBuilder An instance of the `UpdateQueryBuilder`.
     */
    public static function update(string $table): UpdateQueryBuilder
    {
        return (new UpdateQueryBuilder())->table($table);
    }
}
