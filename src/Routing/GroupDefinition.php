<?php declare(strict_types=1);

namespace Lalaz\Routing;

class GroupDefinition
{
    /** @var RouteDefinition[] $routes The routes that are part of this group */
    protected array $routes;

    /** @var string|null $prefix The prefix of the group */
    protected string $prefix;

    public function __construct(array &$routes, string $prefix)
    {
        $this->routes = &$routes;
        $this->prefix = $prefix;
    }

    /**
     * Sets middleware for all routes within the group.
     *
     * @param array $middleware middleware class names.
     * @return $this
     */
    public function middleware($middleware): GroupDefinition
    {
        return $this->middlewares([$middleware]);
    }

    /**
     * Sets middlewares for all routes within the group.
     *
     * @param array $middlewares An array of middleware class names.
     * @return $this
     */
    public function middlewares($middlewares = array()): GroupDefinition
    {
        foreach ($this->routes as $route) {
            $route->middlewares($middlewares);
        }

        return $this;
    }

    /**
     * Add authentication middleware to the route.
     *
     * @return $this
     */
    public function useAuthentication(string $loginUrl = ''): GroupDefinition
    {
        foreach ($this->routes as $route) {
            $route->useAuthentication($loginUrl);
        }

        return $this;
    }

    /**
     * Add authorization middleware to the route.
     *
     * @param array $roles An array of roles required for the route.
     * @return $this
     */
    public function useAuthorization(array $roles = []): GroupDefinition
    {
        foreach ($this->routes as $route) {
            $route->useAuthorization($roles);
        }

        return $this;
    }
}
