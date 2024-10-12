<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

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
