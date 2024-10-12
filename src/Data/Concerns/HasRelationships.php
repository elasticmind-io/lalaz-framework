<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

use Lalaz\Data\Relation;

trait HasRelationships
{
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
     * @param string $relatedClass
     * @param string|null $pivotTable
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
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
