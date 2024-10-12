<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

trait HasTimestamps
{
    /**
     * Update the timestamps on the model.
     *
     * @return void
     */
    protected function updateTimestamps(): void
    {
        if (!self::hasTimestamps()) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');

        if ($this->hasAttribute('updated_at')) {
            $this->updated_at = $timestamp;
            $this->dirty['updated_at'] = $this->updated_at;
        }

        if (!$this->exists && $this->hasAttribute('created_at')) {
            $this->created_at = $timestamp;
            $this->dirty['created_at'] = $this->created_at;
        }
    }

    protected function hasTimestamps(): bool
    {
        return $this->hasAttribute('created_at')
            || $this->hasAttribute('updated_at');
    }
}
