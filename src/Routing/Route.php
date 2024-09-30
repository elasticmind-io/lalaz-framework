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
 * @author  Elasticmind
 * @namespace Lalaz\Routing
 * @package  elasticmind\lalaz-framework
 * @link     https://lalaz.dev
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
    public static function get($path, $controller, $middlewares = array()): void
    {
        Lalaz::getInstance()->router->get($path, $controller, $middlewares);
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
    public static function post($path, $controller, $middlewares = array()): void
    {
        Lalaz::getInstance()->router->post($path, $controller, $middlewares);
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
    public static function put($path, $controller, $middlewares = array()): void
    {
        Lalaz::getInstance()->router->put($path, $controller, $middlewares);
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
    public static function patch($path, $controller, $middlewares = array()): void
    {
        Lalaz::getInstance()->router->patch($path, $controller, $middlewares);
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
    public static function delete($path, $controller, $middlewares = array()): void
    {
        Lalaz::getInstance()->router->delete($path, $controller, $middlewares);
    }

    /**
     * Registers a middleware to be used for all routes.
     *
     * @param string $middleware The middleware class name to use.
     *
     * @return void
     */
    public static function use($middleware): void
    {
        Lalaz::getInstance()->router->use($middleware);
    }
}
