<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

use Exception;
use PDOException;

use Lalaz\Lalaz;

/**
 * Trait DatabaseWritable
 *
 * This trait encapsulates the logic for saving, updating, and deleting records
 * in the database for ActiveRecord models.
 */
trait DatabaseWritable
{
    /** @var array The attributes that have been changed. */
    protected array $dirty = [];

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
                $this->$primaryKey = Lalaz::getInstance()->db->lastInsertId();
            }

            $this->exists = true;
            $this->original = $this->attributesToArray();
            $this->dirty = [];
            $this->afterCreate();
            return true;
        } catch (PDOException $e) {
            throw new Exception('Error inserting record: ' . $e->getMessage(), 0, $e);
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

        die($sql);

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
        } catch (PDOException $e) {
            throw new Exception('Error updating record: ' . $e->getMessage(), 0, $e);
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

        $instance = new static();

        if ($instance->usesSoftDeletes()) {
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
        } catch (PDOException $e) {
            throw new Exception('Error deleting record: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Soft delete the model (if applicable).
     *
     * @return bool
     * @throws Exception
     */
    protected function softDelete(): bool
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->performUpdate();
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
     * Determine if the model has been modified.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return !empty($this->dirty);
    }

    /**
     * Update model's timestamp
     *
     * @return bool
     */
    public function touch(): bool
    {
        $this->updateTimestamps();
        return $this->performUpdate();
    }
}
