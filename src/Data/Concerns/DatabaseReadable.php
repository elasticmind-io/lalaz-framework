<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

use PDO;

use Lalaz\Data\PagedResult;
use Lalaz\Data\Query\Expr;
use Lalaz\Data\Query\Queries;
use Lalaz\Data\Contracts\QueryBuilderInterface;
use Lalaz\Data\Query\SelectQueryBuilder;

trait DatabaseReadable
{
    /**
     * Execute raw query using sql
     *
     * @param string $sql
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public static function executeRawQuery($sql, array $parameters = [])
    {
        $statement = static::prepareAndBindParameters($sql, $parameters);
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

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
     * @param SelectQueryBuilder $query
     * @return SelectQueryBuilder
     */
    public static function applySoftDeleteConstraint(SelectQueryBuilder $query): SelectQueryBuilder
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

        if ($model && !empty($with)) {
            static::eagerLoadRelations([$model], $with);
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

        if (!empty($results) && !empty($with)) {
            static::eagerLoadRelations($results, $with);
        }

        return $results;
    }

    /**
     * Retrieves a paginated result of all records from the table associated with the class.
     *
     * @param int $currentPage The current page for pagination (default value: 1).
     * @param int $take The number of records to retrieve per page (default value: 50).
     * @param array $orderBy An optional array to define the sorting of the results.
     * @param array $with
     *
     * @return PagedResult An object containing the paginated results, total record count, and other pagination information.
     */
    public static function findAllPaged($currentPage = 1, $take = 50, $orderBy = array(), array $with = array()): PagedResult
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

        if (!empty($result) && !empty($with)) {
            static::eagerLoadRelations($result, $with);
        }

        $paginated = new PagedResult($count, $take, $currentPage, $result);

        return $paginated;
    }

    /**
     * Retrieves a paginated result of all records from the table associated with the class.
     *
     * @param Expr $expr
     * @param int $currentPage The current page for pagination (default value: 1).
     * @param int $take The number of records to retrieve per page (default value: 50).
     * @param array $orderBy An optional array to define the sorting of the results.
     * @param array $with
     *
     * @return PagedResult An object containing the paginated results, total record count, and other pagination information.
     */
    public static function findAllPagedByExpression($expr, $currentPage = 1, $take = 50, $orderBy = array(), array $with = array()): PagedResult
    {
        $tableName = static::tableName();

        $pageIndex = $currentPage - 1;
        $start = $pageIndex * $take;

        $query = Queries::select('*')
            ->from($tableName)
            ->where($expr->expression())
            ->paginate($start, $take);

        $query = static::applySoftDeleteConstraint($query);
        $query = static::applyOrderBy($query, $orderBy);

        $count = static::count();
        $result = static::queryAll($query, $expr->parameters());

        if (!empty($result) && !empty($with)) {
            static::eagerLoadRelations($result, $with);
        }

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
    public static function findOneByExpression(Expr $expr, array $with = []): ?self
    {
        $tableName = static::tableName();

        $query = Queries::select('*')
            ->from($tableName)
            ->where($expr->expression());

        $query = static::applySoftDeleteConstraint($query);

        $result = static::queryOne($query, $expr->parameters());

        if ($result && !empty($with)) {
            static::eagerLoadRelations([$result], $with);
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

        $result = static::queryAll($query, $expr->parameters());

        if (!empty($result) && !empty($with)) {
            static::eagerLoadRelations($result, $with);
        }

        return $result;
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
     * @param SelectQueryBuilder $query
     * @param array $orderBy
     * @return SelectQueryBuilder
     */
    protected static function applyOrderBy(SelectQueryBuilder $query, array $orderBy): SelectQueryBuilder
    {
        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Eager load relationships to avoid N+1 queries.
     *
     * This method loads relationships for multiple models in an efficient way:
     * - 1 query for the base models
     * - 1 query per relationship type (instead of N queries)
     *
     * Example:
     * - Loading 100 users with their posts would be: 1 query for users + 1 query for all posts
     * - Without eager loading: 1 query for users + 100 queries for posts (N+1 problem)
     *
     * @param array $models Array of model instances
     * @param array $relations Array of relation names to load
     * @return void
     * @throws Exception
     */
    protected static function eagerLoadRelations(array $models, array $relations): void
    {
        if (empty($models) || empty($relations)) {
            return;
        }

        foreach ($relations as $relationName) {
            // Get the relation instance from the first model to inspect metadata
            $firstModel = $models[0];

            if (!method_exists($firstModel, $relationName)) {
                continue;
            }

            /** @var \Lalaz\Data\Relation $relation */
            $relation = $firstModel->$relationName();
            $relationType = $relation->getRelationType();
            $relatedClass = $relation->getRelatedClass();
            $foreignKey = $relation->getForeignKey();

            // Get all IDs from the parent models
            $parentKey = $relation->getLocalKey() ?? static::primaryKey();
            $parentIds = [];

            foreach ($models as $model) {
                $value = $model->$parentKey ?? null;
                if ($value !== null) {
                    $parentIds[] = $value;
                }
            }

            if ($parentIds === []) {
                continue;
            }

            // Build the eager load query based on relationship type
            switch ($relationType) {
                case 'hasMany':
                case 'hasOne':
                    static::eagerLoadHasRelation($models, $relationName, $relatedClass, $foreignKey, $parentKey, $parentIds, $relationType === 'hasOne');
                    break;

                case 'belongsTo':
                    static::eagerLoadBelongsToRelation($models, $relationName, $relatedClass, $foreignKey, $relation->getOwnerKey(), $relationType);
                    break;

                case 'belongsToMany':
                    static::eagerLoadBelongsToManyRelation($models, $relationName, $relation, $parentKey, $parentIds);
                    break;
            }
        }
    }

    /**
     * Eager load hasMany or hasOne relationships.
     *
     * @param array $models
     * @param string $relationName
     * @param string $relatedClass
     * @param string $foreignKey
     * @param string $parentKey
     * @param array $parentIds
     * @param bool $isHasOne
     * @return void
     * @throws Exception
     */
    protected static function eagerLoadHasRelation(
        array $models,
        string $relationName,
        string $relatedClass,
        string $foreignKey,
        string $parentKey,
        array $parentIds,
        bool $isHasOne
    ): void {
        // Build WHERE IN query for all parent IDs at once
        $placeholders = [];
        $parameters = [];

        foreach ($parentIds as $index => $id) {
            $placeholder = ":id_$index";
            $placeholders[] = $placeholder;
            $parameters["id_$index"] = $id;
        }

        $relatedModel = new $relatedClass();
        $tableName = $relatedModel::tableName();

        $placeholderList = implode(',', $placeholders);

        $query = Queries::select('*')
            ->from($tableName)
            ->where("$foreignKey IN ($placeholderList)");

        $query = $relatedModel::applySoftDeleteConstraint($query);

        $relatedModels = $relatedModel::queryAll($query, $parameters);

        // Group related models by foreign key
        $grouped = [];
        foreach ($relatedModels as $relatedModel) {
            $fkValue = $relatedModel->$foreignKey;
            if ($isHasOne) {
                $grouped[$fkValue] = $relatedModel;
            } else {
                if (!isset($grouped[$fkValue])) {
                    $grouped[$fkValue] = [];
                }
                $grouped[$fkValue][] = $relatedModel;
            }
        }

        // Assign to parent models
        foreach ($models as $model) {
            $parentId = $model->$parentKey;
            $model->$relationName = $grouped[$parentId] ?? ($isHasOne ? null : []);
        }
    }

    /**
     * Eager load belongsTo relationships.
     *
     * @param array $models
     * @param string $relationName
     * @param string $relatedClass
     * @param string $foreignKey
     * @param string|null $ownerKey
     * @param string $relationType
     * @return void
     * @throws Exception
     */
    protected static function eagerLoadBelongsToRelation(
        array $models,
        string $relationName,
        string $relatedClass,
        string $foreignKey,
        ?string $ownerKey,
        string $relationType
    ): void {
        $relatedModel = new $relatedClass();
        $ownerKey = $ownerKey ?? $relatedModel::primaryKey();

        // Get all foreign key values
        $foreignIds = [];
        foreach ($models as $model) {
            $value = $model->$foreignKey ?? null;
            if ($value !== null) {
                $foreignIds[] = $value;
            }
        }

        if ($foreignIds === []) {
            foreach ($models as $model) {
                $model->$relationName = null;
            }
            return;
        }

        // Build WHERE IN query
        $placeholders = [];
        $parameters = [];

        foreach ($foreignIds as $index => $id) {
            $placeholder = ":id_$index";
            $placeholders[] = $placeholder;
            $parameters["id_$index"] = $id;
        }

        $tableName = $relatedModel::tableName();
        $placeholderList = implode(',', $placeholders);

        $query = Queries::select('*')
            ->from($tableName)
            ->where("$ownerKey IN ($placeholderList)");

        $query = $relatedModel::applySoftDeleteConstraint($query);

        $relatedModels = $relatedModel::queryAll($query, $parameters);

        // Index by owner key
        $indexed = [];
        foreach ($relatedModels as $related) {
            $indexed[$related->$ownerKey] = $related;
        }

        // Assign to parent models
        foreach ($models as $model) {
            $fkValue = $model->$foreignKey ?? null;
            $model->$relationName = $indexed[$fkValue] ?? null;
        }
    }

    /**
     * Eager load belongsToMany relationships.
     *
     * @param array $models
     * @param string $relationName
     * @param \Lalaz\Data\Relation $relation
     * @param string $parentKey
     * @param array $parentIds
     * @return void
     * @throws Exception
     */
    protected static function eagerLoadBelongsToManyRelation(
        array $models,
        string $relationName,
        $relation,
        string $parentKey,
        array $parentIds
    ): void {
        $pivotTable = $relation->getPivotTable();
        $foreignPivotKey = $relation->getForeignKey();
        $relatedPivotKey = $relation->getRelatedPivotKey();
        $relatedClass = $relation->getRelatedClass();
        $ownerKey = $relation->getOwnerKey();

        $relatedModel = new $relatedClass();
        $tableName = $relatedModel::tableName();

        // Build query with pivot table
        $placeholders = [];
        $parameters = [];

        foreach ($parentIds as $index => $id) {
            $placeholder = ":id_$index";
            $placeholders[] = $placeholder;
            $parameters["id_$index"] = $id;
        }

        $placeholderList = implode(',', $placeholders);

        $query = Queries::select("$tableName.*, $pivotTable.$foreignPivotKey as pivot_parent_id")
            ->from($tableName)
            ->innerJoin("$pivotTable ON $pivotTable.$relatedPivotKey = $tableName.$ownerKey")
            ->where("$pivotTable.$foreignPivotKey IN ($placeholderList)");

        $query = $relatedModel::applySoftDeleteConstraint($query);

        $relatedModels = $relatedModel::queryAll($query, $parameters);

        // Group by parent ID
        $grouped = [];
        foreach ($relatedModels as $relatedModel) {
            $parentId = $relatedModel->pivot_parent_id;
            if (!isset($grouped[$parentId])) {
                $grouped[$parentId] = [];
            }
            // Remove pivot helper field
            unset($relatedModel->pivot_parent_id);
            $grouped[$parentId][] = $relatedModel;
        }

        // Assign to parent models
        foreach ($models as $model) {
            $parentId = $model->$parentKey;
            $model->$relationName = $grouped[$parentId] ?? [];
        }
    }
}
