<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

trait HasFillableAttributes
{
    /** @var array The attributes that are mass assignable. */
    protected array $fillable = [];

    /** @var array The attributes that are not mass assignable. */
    protected array $guarded = ['id'];

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        // Get the fillable attributes or all attributes if fillable is not defined
        $fillable = $this->getFillable();

        foreach ($attributes as $key => $value) {
            // Skip guarded attributes
            if (in_array($key, $this->guarded)) {
                continue;
            }

            // Only assign fillable attributes
            if (in_array($key, $fillable)) {
                $this->__set($key, $value);
            }
        }

        return $this;
    }

    /**
     * Get the attributes that are mass assignable.
     *
     * @return array
     */
    public function getFillable(): array
    {
        return !empty($this->fillable) ? $this->fillable : array_keys(get_object_vars($this));
    }

    /**
     * Get the fillable attributes with their current values.
     *
     * @return array
     */
    public function getFillableAttributes(): array
    {
        $attributes = [];
        $fillable = $this->getFillable();

        foreach ($fillable as $attribute) {
            if (!empty($this->guarded) && in_array($attribute, $this->guarded)) {
                continue;
            }

            if (property_exists($this, $attribute)) {
                $attributes[$attribute] = $this->$attribute;
            }
        }

        return $attributes;
    }
}
