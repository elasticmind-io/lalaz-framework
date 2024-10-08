<?php declare(strict_types=1);

namespace Lalaz\Routing;

use Lalaz\Security\Middleware\AuthenticationMiddleware;
use Lalaz\Security\Middleware\AuthorizationMiddleware;
use Lalaz\Security\Middleware\PermissionMiddleware;

/**
 * Class RouteDefinition
 *
 * This class encapsulates the definition of a route in the application. It stores information
 * about the HTTP method, path, controller, function, and associated middlewares. Additionally,
 * it provides methods to manipulate and query the route's properties.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class RouteDefinition
{
    /**
     * @var string The HTTP method of the route (GET, POST, etc.)
     */
    protected string $method;

    /**
     * @var string The URI path for the route.
     */
    protected string $path;

    /**
     * @var string The controller class associated with the route.
     */
    protected string $controller;

    /**
     * @var string The function/method in the controller to be executed.
     */
    protected string $function;

    /**
     * @var array Parameters extracted from the route path.
     */
    protected array $params;

    /**
     * @var array List of middleware classes to be applied to the route.
     */
    protected array $middlewares = [];

    /**
     * RouteDefinition constructor.
     *
     * Initializes a new route definition with the specified method, path, controller, and middlewares.
     *
     * @param string $method The HTTP method for the route (e.g., 'GET', 'POST').
     * @param string $path The URI path for the route.
     * @param string $controller The controller class name associated with the route.
     * @param string $function The method name in the controller to be executed.
     * @param array $middlewares An optional array of middleware classes to be applied to the route.
     */
    public function __construct(string $method, string $path, string $controller, string $function, array $middlewares)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->controller = $controller;
        $this->function = $function;
        $this->middlewares = $middlewares;
        $this->params = static::extractParams($path);
    }

    /**
     * Gets the controller class associated with the route.
     *
     * @return string The controller class name.
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Gets the method name to be executed in the controller.
     *
     * @return string The function name in the controller.
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Gets the list of middleware classes associated with the route.
     *
     * @return array An array of middleware class names.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Gets the URI path for the route.
     *
     * @return string The URI path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets the HTTP method for the route.
     *
     * @return string The HTTP method (e.g., 'GET', 'POST').
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets the parameters extracted from the route's URI path.
     *
     * @return array An array of parameter names.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Sets middlewares for the route.
     *
     * @param array $middlewares An array of middleware class names to be applied.
     * @return RouteDefinition Returns the current route definition for method chaining.
     */
    public function middlewares(array $middlewares): RouteDefinition
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * Adds authentication middleware to the route.
     *
     * @return RouteDefinition Returns the current route definition for method chaining.
     */
    public function useAuthentication(): RouteDefinition
    {
        $this->middlewares[] = AuthenticationMiddleware::class;
        return $this;
    }

    /**
     * Adds authorization middleware to the route.
     *
     * @param array $roles An array of roles required to access the route.
     * @return RouteDefinition Returns the current route definition for method chaining.
     */
    public function useAuthorization(array $roles = []): RouteDefinition
    {
        $this->middlewares[] = new AuthorizationMiddleware($roles);
        return $this;
    }

    /**
     * Add permission middleware to the route.
     *
     * @param array $permissions An array of permissions required for the route.
     * @return $this
     */
    public function usePermissions(array $permissions = []): RouteDefinition
    {
        $this->middlewares[] = new PermissionMiddleware($permissions);
        return $this;
    }

    /**
     * Extracts parameters from the URI path.
     *
     * This method uses regex to identify any parameters in curly braces (e.g., {id}).
     *
     * @param string $path The URI path to extract parameters from.
     * @return array An array of parameter names found in the path.
     */
    private static function extractParams(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        return $matches[1];
    }
}
