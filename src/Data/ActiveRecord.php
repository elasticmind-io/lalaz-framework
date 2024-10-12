<?php declare(strict_types=1);

namespace Lalaz\Data;

use Exception;
use PDO;
use PDOStatement;
use PDOException;

use Lalaz\Lalaz;

use Lalaz\Data\Query\Queries;
use Lalaz\Data\Query\Expr;
use Lalaz\Data\Query\Expressions;
use Lalaz\Data\Query\QueryBuilderInterface;

use Lalaz\Data\Concerns\Presentable;
use Lalaz\Data\Concerns\DatabaseQueryable;
use Lalaz\Data\Concerns\DatabaseReadable;
use Lalaz\Data\Concerns\DatabaseWritable;
use Lalaz\Data\Concerns\HasRelationships;
use Lalaz\Data\Concerns\HasFillableAttributes;

/**
 * Class ActiveRecord
 *
 * This abstract class provides Active Record functionality for models, allowing for easy interaction with the database.
 * It includes methods for creating, updating, deleting, and querying records from the database using the query builder.
 * Models extending this class should implement the `tableName()` method to specify the associated database table.
 *
 * @package  elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link     https://lalaz.dev
 */
abstract class ActiveRecord extends Model
{
    use Presentable;
    use DatabaseQueryable;
    use DatabaseReadable;
    use DatabaseWritable;
    use HasRelationships;
    use HasFillableAttributes;

    /** @var array The attributes that should be cast to native types. */
    protected array $casts = [];

    /** @var array The original attributes before any changes. */
    protected array $original = [];

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
                    return $value;
            }
        }
        return $value;
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
     * Determine if the model uses soft deletes.
     *
     * @return bool
     */
    protected function usesSoftDeletes(): bool
    {
        return $this->hasAttribute('deleted_at');
    }

    /**
     * Start a database transaction.
     *
     * @return void
     */
    public static function beginTransaction(): void
    {
        Lalaz::getInstance()->db->beginTransaction();
    }

    /**
     * Commit a database transaction.
     *
     * @return void
     */
    public static function commit(): void
    {
        Lalaz::getInstance()->db->commit();
    }

    /**
     * Roll back a database transaction.
     *
     * @return void
     */
    public static function rollBack(): void
    {
        Lalaz::getInstance()->db->rollBack();
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
        $db = Lalaz::getInstance()->db;

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
}
