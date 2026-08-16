<?php declare(strict_types=1);

namespace Lalaz\Validation\Concerns;

/**
 * Trait HasAttributes
 *
 * Provides attribute access functionality for models,
 * allowing checking for attribute existence and getting attribute values.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
trait HasAttributes
{
    /**
     * Determine if the model has the given attribute.
     *
     * @param string $key
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return property_exists($this, $key);
    }

    /**
     * Get the given attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        if (!$this->hasAttribute($key)) {
            return '';
        }

        return $this->{$key};
    }
}
