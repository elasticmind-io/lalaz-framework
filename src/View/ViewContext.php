<?php declare(strict_types=1);

namespace Lalaz\View;

use Lalaz\Core\LazyValue;

/**
 * Class ViewContext
 *
 * Manages global view data and variables that are available across all views.
 * Supports lazy evaluation of variables through closures for performance optimization.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class ViewContext
{
    protected static array $data = [];

    /**
     * Set a view variable, which can be a direct value or a closure (lazy)
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }

    /**
     * Get a view variable by key. Call it if it's a closure.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, self::$data)) {
            return $default;
        }

        $value = self::$data[$key];

        return is_callable($value) ? $value() : $value;
    }

    /**
     * Return all resolved view data.
     *
     * @return array
     */
    public static function resolved(): array
    {
        $resolved = [];

        foreach (self::$data as $key => $value) {
            $resolved[$key] = is_callable($value)
                ? new LazyValue($value)
                : $value;
        }

        return $resolved;
    }

    /**
     * Reset all view data.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$data = [];
    }
}
