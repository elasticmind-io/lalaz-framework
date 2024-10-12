<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

use PDO;

use Lalaz\Data\PagedResult;
use Lalaz\Data\Query\Expr;
use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\QueryBuilderInterface;

trait DatabaseReadable
{
    /**
     * Execute a query and fetch a single record.
     *
     * @param QueryBuilderInterface $builder
     * @param array $parameters
     * @return static|null
     * @throws Exception
     */
    public static function queryOne(QueryBuilderInterface $builder, array $parameters = [])
    {
        $sql = $builder->build();

        $statement = static::prepareAndBindParameters($sql, $parameters);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $model = new static();

            foreach ($result as $attribute => $value) {
                $model->$attribute = $value;
            }

            $model->exists = true;
            $model->original = $model->attributesToArray();
            $model->dirty = [];
            return $model;
        }

        return null;
    }

    /**
     * Execute a query and fetch all records.
     *
     * @param QueryBuilderInterface $builder
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public static function queryAll(QueryBuilderInterface $builder, array $parameters = []): array
    {
        $sql = $builder->build();

        $statement = static::prepareAndBindParameters($sql, $parameters);
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $models = [];

        foreach ($results as $result) {
            $model = new static();

            foreach ($result as $attribute => $value) {
                $model->$attribute = $value;
            }

            $model->exists = true;
            $model->original = $model->attributesToArray();
            $model->dirty = [];
            $models[] = $model;
        }

        return $models;
    }

    /**
     * Apply soft delete constraint to the query.
     *
     * @param QueryBuilderInterface $query
     * @return QueryBuilderInterface
     */
    public static function applySoftDeleteConstraint(QueryBuilderInterface $query): QueryBuilderInterface
    {
        $instance = new static();

        if ($instance->usesSoftDeletes()) {
            $query->andWhere('deleted_at IS NULL');
        }

        return $query;
    }

    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @param array $with
     * @return static|null
     * @throws Exception
     */
    public static function findById($id, array $with = [])
    {
        $tableName = static::tableName();
        $primaryKey = static::primaryKey();

        $query = Queries::select('*')->from($tableName);

        if (is_array($primaryKey)) {
            $conditions = [];
            $parameters = [];
            foreach ($primaryKey as $key) {
                $conditions[] = "$key = :$key";
                $parameters[$key] = $id[$key];
            }
            $query->where(implode(' AND ', $conditions));
        } else {
            $query->where("$primaryKey = :$primaryKey");
            $parameters = [$primaryKey => $id];
        }

        $query = static::applySoftDeleteConstraint($query);

        $model = static::queryOne($query, $parameters);

        if ($model) {
            $model->exists = true;
            $model->original = $model->attributesToArray();
            $model->dirty = [];

            foreach ($with as $relation) {
                $model->$relation = $model->$relation()->get();
            }
        }

        return $model;
    }

    /**
     * Find all models.
     *
     * @param array $orderBy
     * @param array $with
     * @return array
     * @throws Exception
     */
    public static function findAll(array $orderBy = [], array $with = []): array
    {
        $tableName = static::tableName();

        $query = Queries::select('*')->from($tableName);
        $query = static::applySoftDeleteConstraint($query);
        $query = static::applyOrderBy($query, $orderBy);

        $results = static::queryAll($query);

        foreach ($results as $model) {
            foreach ($with as $relation) {
                $model->$relation = $model->$relation()->get();
            }
        }

        return $results;
    }

    /**
     * Retrieves a paginated result of all records from the table associated with the class.
     *
     * @param int $currentPage The current page for pagination (default value: 1).
     * @param int $take The number of records to retrieve per page (default value: 50).
     * @param array $orderBy An optional array to define the sorting of the results.
     *
     * @return PagedResult An object containing the paginated results, total record count, and other pagination information.
     */
    public static function findAllPaged($currentPage = 1, $take = 50, $orderBy = array()): PagedResult
    {
        $tableName = static::tableName();

        $pageIndex = $currentPage - 1;
        $start = $pageIndex * $take;

        $query = Queries::select('*')
            ->from($tableName)
            ->paginate($start, $take);

        $query = static::applySoftDeleteConstraint($query);
        $query = static::applyOrderBy($query, $orderBy);

        $count = static::count();
        $result = static::queryAll($query);

        $paginated = new PagedResult($count, $take, $currentPage, $result);

        return $paginated;
    }

    /**
     * Find one model matching the given expression.
     *
     * @param Expr $expr
     * @param array $orderBy
     * @param array $with
     * @return static|null
     * @throws Exception
     */
    public static function findOneByExpression(Expr $expr, array $orderBy = [], array $with = []): ?self
    {
        $tableName = static::tableName();

        $query = Queries::select('*')
            ->from($tableName)
            ->where($expr->expression());

        $query = static::applySoftDeleteConstraint($query);
        $query = static::applyOrderBy($query, $orderBy);

        $result = static::queryOne($query, $expr->parameters());

        if (!$result) {
            return null;
        }

        foreach ($with as $relation) {
            $model->$relation = $model->$relation()->get();
        }

        return $result;
    }

    /**
     * Find all models matching the given expression.
     *
     * @param Expr $expr
     * @param array $orderBy
     * @param array $with
     * @return array
     * @throws Exception
     */
    public static function findAllByExpression(Expr $expr, array $orderBy = [], array $with = []): array
    {
        $tableName = static::tableName();

        $query = Queries::select('*')
            ->from($tableName)
            ->where($expr->expression());

        $query = static::applySoftDeleteConstraint($query);
        $query = static::applyOrderBy($query, $orderBy);

        $results = static::queryAll($query, $expr->parameters());

        foreach ($results as $model) {
            foreach ($with as $relation) {
                $model->$relation = $model->$relation()->get();
            }
        }

        return $results;
    }

    /**
     * Count the total number of records.
     *
     * @return int
     * @throws Exception
     */
    public static function count(): int
    {
        $tableName = static::tableName();

        $query = Queries::select('COUNT(*) AS count')->from($tableName);

        $query = static::applySoftDeleteConstraint($query);

        $sql = $query->build();

        $statement = static::prepare($sql);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Count the number of records matching the given expression.
     *
     * @param Expr $expr
     * @return int
     * @throws Exception
     */
    public static function countByExpression(Expr $expr): int
    {
        $tableName = static::tableName();

        $query = Queries::select('COUNT(*) AS count')->from($tableName)
            ->where($expr->expression());

        $query = static::applySoftDeleteConstraint($query);

        $sql = $query->build();

        $statement = static::prepareAndBindParameters($sql, $expr->parameters());
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Apply ORDER BY clauses to the query builder.
     *
     * @param QueryBuilderInterface $query
     * @param array $orderBy
     * @return QueryBuilderInterface
     */
    protected static function applyOrderBy(QueryBuilderInterface $query, array $orderBy): QueryBuilderInterface
    {
        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }
}
