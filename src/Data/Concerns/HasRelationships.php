<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

use Lalaz\Data\Relation;

/**
 * Trait HasRelationships
 *
 * Provides relationship functionality for ActiveRecord models,
 * supporting hasMany, belongsTo, and other common relationship types.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
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
        $foreignKey = $foreignKey ?: strtolower($this->classBaseName(static::class)) . '_id';
        $localKey = $localKey ?: static::primaryKey();

        $localValue = $this->resolveAttributeValue($localKey);

        return new Relation($relatedClass, $foreignKey, $localValue, 'hasMany', $localKey);
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
        $foreignKey = $foreignKey ?: strtolower($this->classBaseName(static::class)) . '_id';
        $localKey = $localKey ?: static::primaryKey();

        $localValue = $this->resolveAttributeValue($localKey);

        return new Relation($relatedClass, $foreignKey, $localValue, 'hasOne', $localKey);
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
        $foreignKey = $foreignKey ?: strtolower($this->classBaseName($relatedClass)) . '_id';
        $ownerKey = $ownerKey ?: (new $relatedClass())->primaryKey();
        return new Relation($relatedClass, $foreignKey, $this->$foreignKey, 'belongsTo', null, $ownerKey);
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
        $base = strtolower($this->classBaseName($this));
        $related = strtolower($this->classBaseName($relatedClass));

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
        return strtolower($this->classBaseName($this)) . '_id';
    }

    /**
     * Resolve attribute value from primary/local key supporting scalar or array keys.
     */
    private function resolveAttributeValue(string|array $key): mixed
    {
        if (is_array($key)) {
            return array_map(fn ($k) => $this->{$k} ?? null, $key);
        }

        return $this->{$key} ?? null;
    }

    /**
     * Retrieve base class name without namespace for strings or objects.
     */
    private function classBaseName(object|string $subject): string
    {
        $fqcn = is_object($subject) ? $subject::class : $subject;
        $position = strrpos($fqcn, '\\');

        return $position === false ? $fqcn : substr($fqcn, $position + 1);
    }
}
