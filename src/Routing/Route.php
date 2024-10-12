<?php declare(strict_types=1);

namespace Lalaz\Routing;

use Lalaz\Lalaz;

/**
 * Class Route
 *
 * This class provides static methods to define routes in a more concise way.
 * It interacts with the application's main router instance to register HTTP
 * methods (GET, POST, PUT, PATCH, DELETE) and middleware.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class Route
{
    /**
     * Registers a GET route with the application router.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     *
     * @return void
     */
    public static function get($path, $controller, $middlewares = array()): RouteDefinition
    {
        return Lalaz::router()
            ->get($path, $controller, $middlewares);
    }

    /**
     * Registers a POST route with the application router.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     *
     * @return void
     */
    public static function post($path, $controller, $middlewares = array()): RouteDefinition
    {
        return Lalaz::router()
            ->post($path, $controller, $middlewares);
    }

    /**
     * Registers a PUT route with the application router.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     *
     * @return void
     */
    public static function put($path, $controller, $middlewares = array()): RouteDefinition
    {
        return Lalaz::router()
            ->put($path, $controller, $middlewares);
    }

    /**
     * Registers a PATCH route with the application router.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     *
     * @return void
     */
    public static function patch($path, $controller, $middlewares = array()): RouteDefinition
    {
        return Lalaz::router()
            ->patch($path, $controller, $middlewares);
    }

    /**
     * Registers a DELETE route with the application router.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     *
     * @return void
     */
    public static function delete($path, $controller, $middlewares = array()): RouteDefinition
    {
        return Lalaz::router()
            ->delete($path, $controller, $middlewares);
    }

    public static function group(string $prefix, callable $callback): GroupDefinition
    {
        return Lalaz::router()
            ->group($prefix, $callback);
    }

    /**
     * Registers a middleware to be used for all routes.
     *
     * @param string $middleware The middleware class name to use.
     *
     * @return void
     */
    public static function use($middleware): Router
    {
        return Lalaz::router()
            ->use($middleware);
    }
}
