<?php declare(strict_types=1);

namespace Lalaz\Data;

use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\IQueryBuilder;
use Exception;

/**
 * Class Relation
 *
 * Manages relationships between models, such as hasMany, hasOne, belongsTo, and belongsToMany.
 *
 * @package Lalaz\Data
 */
class Relation
{
    /** @var string The related model class */
    protected string $relatedClass;

    /** @var string The foreign key in the related model or pivot table */
    protected string $foreignKey;

    /** @var mixed The value of the local key in the current model */
    protected $localValue;

    /** @var string The type of relationship (hasMany, hasOne, belongsTo, belongsToMany) */
    protected string $relationType;

    /** @var string|null The local key in the current model */
    protected ?string $localKey;

    /** @var string|null The owner key in the related model */
    protected ?string $ownerKey;

    /** @var IQueryBuilder The query builder instance */
    protected IQueryBuilder $query;

    /** @var string|null The pivot table name (used in belongsToMany) */
    protected ?string $pivotTable;

    /** @var string|null The related pivot key in the pivot table (belongsToMany) */
    protected ?string $relatedPivotKey;

    /**
     * Relation constructor.
     *
     * @param string      $relatedClass     The related model class.
     * @param string      $foreignKey       The foreign key in the related model or pivot table.
     * @param mixed       $localValue       The value of the local key in the current model.
     * @param string      $relationType     The type of relationship.
     * @param string|null $localKey         The local key in the current model.
     * @param string|null $ownerKey         The owner key in the related model.
     * @param string|null $pivotTable       The pivot table name (for belongsToMany).
     * @param string|null $relatedPivotKey  The related pivot key in the pivot table.
     */
    public function __construct(
        string $relatedClass,
        string $foreignKey,
        $localValue,
        string $relationType,
        ?string $localKey = null,
        ?string $ownerKey = null,
        ?string $pivotTable = null,
        ?string $relatedPivotKey = null
    ) {
        $this->relatedClass = $relatedClass;
        $this->foreignKey = $foreignKey;
        $this->localValue = $localValue;
        $this->relationType = $relationType;
        $this->localKey = $localKey;
        $this->ownerKey = $ownerKey;
        $this->pivotTable = $pivotTable;
        $this->relatedPivotKey = $relatedPivotKey;

        $this->initializeQuery();
    }

    /**
     * Initializes the query builder based on the relationship type.
     *
     * @return void
     */
    protected function initializeQuery(): void
    {
        /** @var ActiveRecord $relatedModel */
        $relatedModel = $this->getRelatedModel();
        $tableName = $relatedModel::tableName();
        $this->query = Queries::select("$tableName.*")->from($tableName);

        switch ($this->relationType) {
            case 'hasMany':
            case 'hasOne':
                $this->query->where("$tableName.$this->foreignKey = :foreignKeyValue");
                $this->query->parameters(['foreignKeyValue' => $this->localValue]);
                break;

            case 'belongsTo':
                $ownerKey = $this->ownerKey ?? $relatedModel::primaryKey();
                $this->query->where("$tableName.$ownerKey = :ownerKeyValue");
                $this->query->parameters(['ownerKeyValue' => $this->localValue]);
                break;

            case 'belongsToMany':
                $pivotTable = $this->pivotTable;
                $foreignPivotKey = $this->foreignKey;
                $relatedPivotKey = $this->relatedPivotKey;
                $localKey = $this->localKey;
                $relatedKey = $this->ownerKey ?? $relatedModel::primaryKey();

                // Join the pivot table
                $this->query->join(
                    $pivotTable,
                    "$pivotTable.$relatedPivotKey = $tableName.$relatedKey"
                );

                // Where clause on the pivot table
                $this->query->where("$pivotTable.$foreignPivotKey = :foreignKeyValue");
                $this->query->parameters(['foreignKeyValue' => $this->localValue]);
                break;
        }
    }

    /**
     * Executes the query and retrieves the related model(s).
     *
     * @return mixed An instance or array of related models.
     * @throws Exception
     */
    public function get()
    {
        /** @var ActiveRecord $relatedModel */
        $relatedModel = $this->getRelatedModel();

        // Apply soft delete constraint if necessary
        $this->query = $relatedModel::applySoftDeleteConstraint($this->query);

        if ($this->relationType === 'hasOne' || $this->relationType === 'belongsTo') {
            return $relatedModel::queryOne($this->query, $this->query->getParameters());
        } else {
            return $relatedModel::queryAll($this->query, $this->query->getParameters());
        }
    }

    /**
     * Returns the related model class name.
     *
     * @return string
     */
    protected function getRelatedModel(): string
    {
        return $this->relatedClass;
    }

    /**
     * Adds additional constraints to the relationship query.
     *
     * @param callable $callback A callback that receives the query builder.
     * @return $this
     */
    public function where(callable $callback): self
    {
        $this->query = $callback($this->query);
        return $this;
    }

    /**
     * Adds an ORDER BY clause to the relationship query.
     *
     * @param string $column    The column to order by.
     * @param string $direction The direction ('ASC' or 'DESC').
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Sets a limit on the number of records retrieved.
     *
     * @param int $limit The number of records to retrieve.
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Sets an offset for the records retrieved.
     *
     * @param int $offset The offset value.
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * Get the type of the relationship.
     *
     * @return string
     */
    public function getRelationType(): string
    {
        return $this->relationType;
    }

    /**
     * Get the pivot table name (for belongsToMany relationships).
     *
     * @return string|null
     */
    public function getPivotTable(): ?string
    {
        return $this->pivotTable;
    }

    /**
     * Get the related pivot key in the pivot table.
     *
     * @return string|null
     */
    public function getRelatedPivotKey(): ?string
    {
        return $this->relatedPivotKey;
    }

    /**
     * Get the local key in the current model.
     *
     * @return string|null
     */
    public function getLocalKey(): ?string
    {
        return $this->localKey;
    }

    /**
     * Get the foreign key in the related model or pivot table.
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the owner key in the related model.
     *
     * @return string|null
     */
    public function getOwnerKey(): ?string
    {
        return $this->ownerKey;
    }

    /**
     * Add a JOIN clause to the query.
     *
     * @param string $table      The table to join.
     * @param string $condition  The join condition.
     * @param string $type       The type of join (INNER, LEFT, RIGHT).
     * @return $this
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->query->join($table, $condition, $type);
        return $this;
    }

    /**
     * Add a GROUP BY clause to the query.
     *
     * @param string|array $columns The column(s) to group by.
     * @return $this
     */
    public function groupBy($columns): self
    {
        $this->query->groupBy($columns);
        return $this;
    }

    /**
     * Add a HAVING clause to the query.
     *
     * @param string $condition The HAVING condition.
     * @return $this
     */
    public function having(string $condition): self
    {
        $this->query->having($condition);
        return $this;
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return IQueryBuilder
     */
    public function getQuery(): IQueryBuilder
    {
        return $this->query;
    }
}