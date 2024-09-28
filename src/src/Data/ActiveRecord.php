<?php declare(strict_types=1);

namespace Lalaz\Data;

use Lalaz\Lalaz;
use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\Expr;
use Lalaz\Data\Query\Expressions;
use Lalaz\Data\Query\IQueryBuilder;
use Lalaz\Data\DatabaseException;
use Exception;
use PDO;

/**
 * Class ActiveRecord
 *
 * This abstract class provides Active Record functionality for models, allowing for easy interaction with the database.
 * It includes methods for creating, updating, deleting, and querying records from the database using the query builder.
 * Models extending this class should implement the `tableName()` method to specify the associated database table.
 *
 * @package Lalaz\Data
 */
abstract class ActiveRecord extends Model
{
    use Presentable;

    /** @var array The attributes that are mass assignable. */
    protected array $fillable = [];

    /** @var array The attributes that are not mass assignable. */
    protected array $guarded = ['id'];

    /** @var array The attributes that should be hidden for serialization. */
    protected array $hidden = [];

    /** @var array The attributes that should be cast to native types. */
    protected array $casts = [];

    /** @var bool Indicates if the model exists in the database. */
    protected bool $exists = false;

    /** @var array The original attributes before any changes. */
    protected array $original = [];

    /** @var array The attributes that have been changed. */
    protected array $dirty = [];

    /**
     * Returns the name of the database table associated with this ActiveRecord.
     *
     * @return string The table name.
     */
    abstract public static function tableName(): string;

    /**
     * Returns the primary key(s) of the table.
     *
     * @return string|array The primary key column name(s).
     */
    public static function primaryKey()
    {
        return 'id';
    }

    /**
     * Constructor.
     *
     * @param array $attributes Initial attributes to set on the model.
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributesToArray();
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        $fillable = $this->fillable ?: array_keys(get_object_vars($this));

        foreach ($attributes as $key => $value) {
            if (!empty($this->guarded) && in_array($key, $this->guarded)) {
                continue;
            }
            if (in_array($key, $fillable)) {
                $this->__set($key, $value);
            }
        }

        return $this;
    }

    /**
     * Magic method to get an attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $method = 'get' . ucfirst($key) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (property_exists($this, $key)) {
            $value = $this->$key;
            return $this->castAttribute($key, $value);
        }
        return null;
    }

    /**
     * Magic method to set an attribute.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $method = 'set' . ucfirst($key) . 'Attribute';
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->$key = $value;
        }
        $this->dirty[$key] = $value;
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if ($value === null) {
            return null;
        }
        $casts = $this->casts[$key] ?? null;
        if ($casts) {
            switch ($casts) {
                case 'int':
                case 'integer':
                    return (int) $value;
                case 'real':
                case 'float':
                case 'double':
                    return (float) $value;
                case 'string':
                    die($value);
                    return (string) $value;
                case 'bool':
                case 'boolean':
                    return (bool) $value;
                case 'array':
                    return (array) $value;
                case 'object':
                    return (object) $value;
                case 'json':
                    return json_decode($value, true);
                case 'datetime':
                    return new \DateTime($value);
                default:
                    die($value);
                    return $value;
            }
        }
        return $value;
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        $this->beforeSave();

        if ($this->exists) {
            if ($this->isDirty()) {
                $result = $this->performUpdate();
            } else {
                $result = true;
            }
        } else {
            $result = $this->performInsert();
        }

        $this->afterSave();

        return $result;
    }

    /**
     * Perform an insert operation.
     *
     * @return bool
     * @throws Exception
     */
    protected function performInsert(): bool
    {
        if (!$this->validate('create')) {
            return false;
        }

        $this->beforeCreate();
        $this->updateTimestamps();

        $attributes = $this->getFillableAttributes();

        $tableName = static::tableName();

        $columns = array_keys($attributes);
        $params = array_map(fn($attr) => ":$attr", $columns);

        $sql = "INSERT INTO $tableName (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $params) . ")";
        $statement = static::prepare($sql);

        foreach ($attributes as $attribute => $value) {
            $statement->bindValue(":$attribute", $value);
        }

        try {
            $statement->execute();
            $primaryKey = static::primaryKey();

            if (is_string($primaryKey) && empty($this->$primaryKey)) {
                $this->$primaryKey = Lalaz::$app->db->lastInsertId();
            }

            $this->exists = true;
            $this->original = $this->attributesToArray();
            $this->dirty = [];
            $this->afterCreate();
            return true;
        } catch (\PDOException $e) {
            throw new \Exception('Error inserting record: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Perform an update operation.
     *
     * @return bool
     * @throws Exception
     */
    protected function performUpdate(): bool
    {
        if (!$this->validate('update')) {
            return false;
        }

        $this->beforeUpdate();

        if (!$this->isDirty()) {
            die('update)');
            return true;
        }

        $this->updateTimestamps();

        $attributes = $this->getDirtyAttributes();

        $tableName = static::tableName();

        $columns = array_keys($attributes);
        $setClause = implode(", ", array_map(fn($attr) => "$attr = :$attr", $columns));

        $primaryKey = static::primaryKey();
        $whereClause = $this->buildWhereClause($primaryKey);

        $sql = "UPDATE $tableName SET $setClause WHERE $whereClause";

        $statement = static::prepare($sql);

        foreach ($attributes as $attribute => $value) {
            $statement->bindValue(":$attribute", $value);
        }

        $this->bindPrimaryKeyValues($statement, $primaryKey);

        try {
            $statement->execute();
            $this->original = $this->attributesToArray();
            $this->dirty = [];
            $this->afterUpdate();
            return true;
        } catch (\PDOException $e) {
            throw new DatabaseException('Error updating record: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        $this->beforeDelete();

        if ($this->usesSoftDeletes()) {
            $result = $this->softDelete();
        } else {
            $result = $this->performDeleteOnModel();
        }

        $this->afterDelete();

        return $result;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return bool
     * @throws Exception
     */
    protected function performDeleteOnModel(): bool
    {
        $tableName = static::tableName();

        $primaryKey = static::primaryKey();
        $whereClause = $this->buildWhereClause($primaryKey);

        $sql = "DELETE FROM $tableName WHERE $whereClause";

        $statement = static::prepare($sql);

        $this->bindPrimaryKeyValues($statement, $primaryKey);

        try {
            $statement->execute();
            $this->exists = false;
            return true;
        } catch (\PDOException $e) {
            throw new DatabaseException('Error deleting record: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update the timestamps on the model.
     *
     * @return void
     */
    protected function updateTimestamps(): void
    {
        $timestamp = date('Y-m-d H:i:s');

        if ($this->hasAttribute('updated_at')) {
            $this->updated_at = $timestamp;
            $this->dirty['updated_at'] = $this->updated_at;
        }

        if (!$this->exists && $this->hasAttribute('created_at')) {
            $this->created_at = $timestamp;
            $this->dirty['created_at'] = $this->created_at;
        }
    }

    /**
     * Determine if the model uses soft deletes.
     *
     * @return bool
     */
    protected function usesSoftDeletes(): bool
    {
        return $this->hasAttribute('deleted_at');
    }

    /**
     * Determine if the model has the given attribute.
     *
     * @param string $key
     * @return bool
     */
    protected function hasAttribute($key): bool
    {
        return property_exists($this, $key);
    }

    /**
     * Get the attributes that have been changed.
     *
     * @return array
     */
    protected function getDirtyAttributes(): array
    {
        return $this->dirty;
    }

    /**
     * Get the fillable attributes with their current values.
     *
     * @return array
     */
    protected function getFillableAttributes(): array
    {
        $attributes = [];
        $fillable = $this->fillable ?: array_keys(get_object_vars($this));

        foreach ($fillable as $attribute) {
            if (!empty($this->guarded) && in_array($attribute, $this->guarded)) {
                continue;
            }

            if ($this->hasAttribute($attribute)) {
                $attributes[$attribute] = $this->$attribute;
            }
        }

        return $attributes;
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray(): array
    {
        $attributes = [];
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (in_array($key, $this->hidden)) {
                continue;
            }
            $attributes[$key] = $this->__get($key);
        }
        return $attributes;
    }

    /**
     * Convert the model to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributesToArray();
    }

    /**
     * Convert the model to JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Determine if the model has been modified.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return !empty($this->dirty);
    }

    /**
     * Hook method called before saving the model.
     *
     * @return void
     */
    protected function beforeSave(): void
    {
        // Custom logic before saving
    }

    /**
     * Hook method called after saving the model.
     *
     * @return void
     */
    protected function afterSave(): void
    {
        // Custom logic after saving
    }

    /**
     * Hook method called before creating the model.
     *
     * @return void
     */
    protected function beforeCreate(): void
    {
        // Custom logic before creating
    }

    /**
     * Hook method called after creating the model.
     *
     * @return void
     */
    protected function afterCreate(): void
    {
        // Custom logic after creating
    }

    /**
     * Hook method called before updating the model.
     *
     * @return void
     */
    protected function beforeUpdate(): void
    {
        // Custom logic before updating
    }

    /**
     * Hook method called after updating the model.
     *
     * @return void
     */
    protected function afterUpdate(): void
    {
        // Custom logic after updating
    }

    /**
     * Hook method called before deleting the model.
     *
     * @return void
     */
    protected function beforeDelete(): void
    {
        // Custom logic before deleting
    }

    /**
     * Hook method called after deleting the model.
     *
     * @return void
     */
    protected function afterDelete(): void
    {
        // Custom logic after deleting
    }

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
     * @param \PDOStatement $statement
     * @param string|array $primaryKey
     * @return void
     */
    protected function bindPrimaryKeyValues(\PDOStatement $statement, $primaryKey): void
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
     * Start a database transaction.
     *
     * @return void
     */
    public static function beginTransaction(): void
    {
        Lalaz::$app->db->beginTransaction();
    }

    /**
     * Commit a database transaction.
     *
     * @return void
     */
    public static function commit(): void
    {
        Lalaz::$app->db->commit();
    }

    /**
     * Roll back a database transaction.
     *
     * @return void
     */
    public static function rollBack(): void
    {
        Lalaz::$app->db->rollBack();
    }

    /**
     * Execute a closure within a database transaction.
     *
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    public static function transaction(callable $callback)
    {
        $db = Lalaz::$app->db;
        try {
            $db->beginTransaction();
            $result = $callback();
            $db->commit();
            return $result;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Prepare a SQL statement.
     *
     * @param string $sql
     * @return \PDOStatement
     */
    protected static function prepare(string $sql): \PDOStatement
    {
        return Lalaz::$app->db->prepare($sql);
    }

    /**
     * Prepare a SQL statement and bind parameters.
     *
     * @param string $sql
     * @param array $parameters
     * @return \PDOStatement
     */
    protected static function prepareAndBindParameters(string $sql, array $parameters = []): \PDOStatement
    {
        $statement = static::prepare($sql);
        static::bindParameters($statement, $parameters);
        return $statement;
    }

    /**
     * Bind parameters to a prepared statement.
     *
     * @param \PDOStatement $statement
     * @param array $parameters
     * @return void
     */
    protected static function bindParameters(\PDOStatement $statement, array $parameters = []): void
    {
        foreach ($parameters as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
    }

    /**
     * Execute a query and fetch a single record.
     *
     * @param IQueryBuilder $builder
     * @param array $parameters
     * @return static|null
     * @throws Exception
     */
    protected static function queryOne(IQueryBuilder $builder, array $parameters = [])
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
     * @param IQueryBuilder $builder
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    protected static function queryAll(IQueryBuilder $builder, array $parameters = []): array
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
     * @param IQueryBuilder $query
     * @return IQueryBuilder
     */
    protected static function applySoftDeleteConstraint(IQueryBuilder $query): IQueryBuilder
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
     * Apply ORDER BY clauses to the query builder.
     *
     * @param IQueryBuilder $query
     * @param array $orderBy
     * @return IQueryBuilder
     */
    protected static function applyOrderBy(IQueryBuilder $query, array $orderBy): IQueryBuilder
    {
        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
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

        $query = Queries::select('*')->from($tableName)
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

        $query = Queries::select('*')->from($tableName)
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

    // Additional methods for paginated results, scopes, bulk operations, etc., can be added here.

    /**
     * Define a hasMany relationship.
     *
     * @param string $relatedClass
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return Relation
     */
    public function hasMany(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null): Relation
    {
        $foreignKey = $foreignKey ?: strtolower(static::class) . '_id';
        $localKey = $localKey ?: static::primaryKey();
        return new Relation($relatedClass, $foreignKey, $this->$localKey, 'hasMany');
    }

    /**
     * Define a hasOne relationship.
     *
     * @param string $relatedClass
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return Relation
     */
    public function hasOne(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null): Relation
    {
        $foreignKey = $foreignKey ?: strtolower(static::class) . '_id';
        $localKey = $localKey ?: static::primaryKey();
        return new Relation($relatedClass, $foreignKey, $this->$localKey, 'hasOne');
    }

    /**
     * Define a belongsTo relationship.
     *
     * @param string $relatedClass
     * @param string|null $foreignKey
     * @param string|null $ownerKey
     * @return Relation
     */
    public function belongsTo(string $relatedClass, ?string $foreignKey = null, ?string $ownerKey = null): Relation
    {
        $foreignKey = $foreignKey ?: strtolower($relatedClass) . '_id';
        $ownerKey = $ownerKey ?: (new $relatedClass())->primaryKey();
        return new Relation($relatedClass, $foreignKey, $this->$foreignKey, 'belongsTo', $ownerKey);
    }

    /**
     * Define a belongsToMany relationship.
     *
     * @param string      $relatedClass   The related model class.
     * @param string|null $pivotTable     The name of the pivot table. If null, it will be generated.
     * @param string|null $foreignPivotKey The foreign key name of the current model in the pivot table.
     * @param string|null $relatedPivotKey The foreign key name of the related model in the pivot table.
     * @param string|null $parentKey      The local key on the current model.
     * @param string|null $relatedKey     The local key on the related model.
     * @return Relation
     */
    public function belongsToMany(
        string $relatedClass,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null
    ): Relation {
        $instance = new $relatedClass();

        $pivotTable = $pivotTable ?: $this->joiningTable($relatedClass);
        $foreignPivotKey = $foreignPivotKey ?: $this->foreignPivotKey();
        $relatedPivotKey = $relatedPivotKey ?: $instance->foreignPivotKey();
        $parentKey = $parentKey ?: static::primaryKey();
        $relatedKey = $relatedKey ?: $instance::primaryKey();

        return new Relation(
            $relatedClass,
            $foreignPivotKey,
            $this->$parentKey,
            'belongsToMany',
            $parentKey,
            $relatedKey,
            $pivotTable,
            $relatedPivotKey
        );
    }

    /**
     * Generate the default pivot table name for a belongsToMany relationship.
     *
     * @param string $relatedClass
     * @return string
     */
    protected function joiningTable(string $relatedClass): string
    {
        $base = strtolower(class_basename($this));
        $related = strtolower(class_basename($relatedClass));

        $tables = [$base, $related];
        sort($tables);

        return implode('_', $tables);
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    protected function foreignPivotKey(): string
    {
        return strtolower(class_basename($this)) . '_id';
    }
}
