<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

/**
 * Trait Presentable
 *
 * This trait allows models to be cast to a Presenter, which formats and manages data presentation.
 * It automatically maps the model's attributes to the Presenter, making them accessible.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
trait Presentable
{
    /**
     * Converts the model or collection of models into the given Presenter class.
     *
     * If a collection is passed, it creates a Presenter for each model in the collection.
     *
     * @param string $presenterClass The Presenter class to be used for formatting the model.
     * @return mixed Returns a Presenter instance for a single model or an array of Presenter instances for collections.
     */
    public function toPresenter(string $presenterClass)
    {
        if (is_array($this)) {
            return array_map(function ($item) use ($presenterClass) {
                return $this->createPresenter($item, $presenterClass);
            }, $this);
        }

        return $this->createPresenter($this, $presenterClass);
    }

    /**
     * Creates and populates the Presenter with the model's attributes.
     *
     * This method dynamically sets the Presenter properties based on the model's attributes.
     *
     * @param mixed $model The model to be presented.
     * @param string $presenterClass The Presenter class to be instantiated.
     * @return mixed Returns the Presenter instance with the model's properties populated.
     */
    protected function createPresenter($model, string $presenterClass)
    {
        $presenter = new $presenterClass();
        $reflection = new \ReflectionClass($presenter);
        $presenterProperties = $reflection->getProperties();

        foreach (get_object_vars($model) as $key => $value) {
            foreach ($presenterProperties as $property) {
                if ($property->getName() === $key) {
                    $property->setAccessible(true);
                    $property->setValue($presenter, $value);
                    break;
                }
            }
        }

        return $presenter;
    }
}
