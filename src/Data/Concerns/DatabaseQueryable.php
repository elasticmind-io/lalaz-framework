<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

use PDOStatement;

use Lalaz\Lalaz;

trait DatabaseQueryable
{
    /** @var bool Indicates if the model exists in the database. */
    protected bool $exists = false;

    /**
     * Build the WHERE clause for primary keys.
     *
     * @param string|array $primaryKey
     * @return string
     */
    protected function buildWhereClause($primaryKey): string
    {
        if (is_array($primaryKey)) {
            return implode(' AND ', array_map(fn($key) => "$key = :$key", $primaryKey));
        } else {
            return "$primaryKey = :$primaryKey";
        }
    }

    /**
     * Bind primary key values to a prepared statement.
     *
     * @param PDOStatement $statement
     * @param string|array $primaryKey
     * @return void
     */
    protected function bindPrimaryKeyValues(PDOStatement $statement, $primaryKey): void
    {
        if (is_array($primaryKey)) {
            foreach ($primaryKey as $key) {
                $statement->bindValue(":$key", $this->$key);
            }
        } else {
            $statement->bindValue(":$primaryKey", $this->$primaryKey);
        }
    }

    /**
     * Prepare a SQL statement.
     *
     * @param string $sql
     * @return PDOStatement
     */
    protected static function prepare(string $sql): PDOStatement
    {
        return Lalaz::getInstance()->db->prepare($sql);
    }

    /**
     * Prepare a SQL statement and bind parameters.
     *
     * @param string $sql
     * @param array $parameters
     * @return PDOStatement
     */
    protected static function prepareAndBindParameters(string $sql, array $parameters = []): PDOStatement
    {
        $statement = static::prepare($sql);
        static::bindParameters($statement, $parameters);
        return $statement;
    }

    /**
     * Bind parameters to a prepared statement.
     *
     * @param PDOStatement $statement
     * @param array $parameters
     * @return void
     */
    protected static function bindParameters(PDOStatement $statement, array $parameters = []): void
    {
        foreach ($parameters as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
    }
}
