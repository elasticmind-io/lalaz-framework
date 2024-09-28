<?php declare(strict_types=1);

namespace Lalaz\Data;

/**
 * Trait SoftDeletes
 *
 * This trait adds soft delete functionality to models. Instead of permanently deleting records
 * from the database, the `deleted_at` column is used to mark the record as deleted, allowing
 * it to be restored later. The trait also provides methods for performing permanent deletions
 * (hard delete) and checking if a model has been soft deleted.
 */
trait SoftDeletes
{
    /**
     * The attribute that stores the soft delete timestamp.
     *
     * @var string|null
     */
    public $deleted_at;

    /**
     * Marks the model as deleted by setting the `deleted_at` timestamp.
     *
     * This performs a soft delete, keeping the record in the database but marking it as deleted.
     *
     * @return bool Returns true if the operation is successful.
     */
    public function softDelete()
    {
        if (property_exists($this, 'deleted_at')) {
            $this->deleted_at = date('Y-m-d H:i:s');
            return $this->save();
        }

        throw new \Exception('The model does not support soft delete.');
    }

    /**
     * Restores the model by setting `deleted_at` to null.
     *
     * This reverses the soft delete, making the record active again.
     *
     * @return bool Returns true if the operation is successful.
     */
    public function restore()
    {
        if (property_exists($this, 'deleted_at')) {
            $this->deleted_at = null;
            return $this->save();
        }

        throw new \Exception('The model does not support soft delete.');
    }

    /**
     * Permanently removes the model from the database.
     *
     * This performs a hard delete, completely removing the record.
     *
     * @return bool Returns true if the deletion is successful.
     */
    public function forceDelete()
    {
        $this->beforeDelete();
        $result = $this->performDeleteOnModel();
        $this->afterDelete();
        return $result;
    }

    /**
     * Checks if the model is currently soft deleted.
     *
     * @return bool Returns true if the model is soft deleted.
     */
    public function isDeleted()
    {
        return !is_null($this->deleted_at);
    }
}
