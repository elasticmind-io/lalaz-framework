<?php declare(strict_types=1);

namespace Lalaz\Validation\Concerns;

/**
 * Trait Serializable
 *
 * Provides serialization functionality for models,
 * allowing models to be converted to arrays and JSON format.
 * Also provides the ability to hide specific attributes from serialization.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
trait Serializable
{
    /**
     * Hides specified properties from serialization.
     *
     * @param string ...$props The property names to hide.
     * @return static
     */
    public function hide(string ...$props): static
    {
        foreach ($props as $prop) {
            $this->hidden[] = $prop;
        }
        return $this;
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
            $attributes[$key] = $this->__get($key) ?? $value;
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
