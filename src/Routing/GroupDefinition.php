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
     * Sets middlewares for all routes within the group.
     *
     * @param array $middlewares An array of middleware class names.
     * @return void
     */
    public function middleware($middleware): GroupDefinition
    {
        foreach ($this->routes as $route) {
            $route->middlewares([$middleware]);
        }

        return $this;
    }

    /**
     * Add authentication middleware to the route.
     *
     * @return $this
     */
    public function useAuthentication(): GroupDefinition
    {
        foreach ($this->routes as $route) {
            $route->useAuthentication();
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
