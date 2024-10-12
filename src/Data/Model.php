<?php declare(strict_types=1);

namespace Lalaz\Data;

use Lalaz\Lalaz;

use Lalaz\Data\Concerns\Serializable;
use Lalaz\Data\Concerns\HasValidation;

/**
 * Class Model
 *
 * This abstract class serves as the base model for the application, providing common validation logic,
 * error handling, and utility methods for data manipulation. It defines validation constants, error messages,
 * and methods to validate data according to specified rules.
 *
 * Subclasses should implement the `validates()` method to specify validation rules for their attributes.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
abstract class Model
{
    use Serializable;
    use HasValidation;

    /** @var array The attributes that should be hidden for serialization. */
    protected array $hidden = [];

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
     * Hides specified properties from the model instance.
     *
     * @param string ...$props The property names to hide.
     * @return void
     */
    public function hide(string ...$props): void
    {
        foreach ($props as $prop) {
            $this->hide[] = $prop;
        }
    }
}
