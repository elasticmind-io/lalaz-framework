<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

/**
 * Trait Serializable
 *
 * Provides serialization functionality for ActiveRecord models,
 * allowing models to be converted to arrays and JSON format.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
trait Serializable
{
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
}
