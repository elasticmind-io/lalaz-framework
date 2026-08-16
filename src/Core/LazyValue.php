<?php declare(strict_types=1);

namespace Lalaz\Core;

/**
 * Class LazyValue
 *
 * A wrapper for lazy-evaluated values intended for use in templates.
 * Allows injecting callables into the view context that are only resolved
 * when accessed (e.g., via property or method).
 *
 * Useful for optimizing performance and avoiding unnecessary computation
 * when a view variable is not actually used.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class LazyValue
{
    /**
     * The resolver callable that returns the actual value.
     *
     * @var callable
     */
    protected mixed $resolver;

    /**
     * The resolved value, cached after the first call.
     *
     * @var mixed
     */
    protected mixed $resolved = null;

    /**
     * Whether the resolver has already been executed.
     *
     * @var bool
     */
    protected bool $wasResolved = false;

    /**
     * LazyValue constructor.
     *
     * @param callable $resolver A closure or callable to be lazily evaluated.
     */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Resolves the value if it hasn't been resolved yet.
     *
     * @return mixed The resolved value.
     */
    protected function getValue(): mixed
    {
        if (! $this->wasResolved) {
            $this->resolved = call_user_func($this->resolver);
            $this->wasResolved = true;
        }

        return $this->resolved;
    }

    /**
     * Allows property access on the resolved value.
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name): mixed
    {
        $value = $this->getValue();

        return is_object($value) ? ($value->$name ?? null) : null;
    }

    /**
     * Allows method calls on the resolved value.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->getValue()->$method(...$args);
    }

    /**
     * Checks if a property is set on the resolved value.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        $value = $this->getValue();

        return is_object($value) && isset($value->$name);
    }
}
