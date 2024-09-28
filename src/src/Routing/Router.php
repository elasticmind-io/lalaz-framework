<?php declare(strict_types=1);

namespace Lalaz\Routing;

use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\View\View;

/**
 * Class Router
 *
 * This class handles the routing of HTTP requests to controllers and methods within the application.
 * It supports various HTTP methods and middleware integration, providing a flexible system for defining routes.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Routing
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class Router
{
    /** @var array $routes Stores all the registered routes */
    protected $routes = [];

    /** @var array $middlewares Stores the global middleware to be applied on all routes */
    protected $middlewares = [];

    /**
     * Adds middleware to the router.
     *
     * @param string $middleware The middleware class name to use.
     * @return Router
     */
    public function use($middleware): Router
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Registers a new route with the specified HTTP method, path, controller, and middlewares.
     *
     * @param string $method The HTTP method (GET, POST, etc.).
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     * @return Router
     */
    public function route($method, $path, $controller, $middlewares = array()): Router
    {
        [$controllerName, $function] = explode('@', $controller);
        $controllerClassName = $this->controllerLookup($controllerName);

        if (!$controllerClassName) {
            die("Controller ${controllerName} was not found!");
        }

        $this->map($method, $path, $controllerClassName, $function, $middlewares);

        return $this;
    }

    /**
     * Registers a GET route.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     * @return Router
     */
    public function get($path, $controller, $middlewares = array()): Router
    {
        return $this->route('GET', $path, $controller, $middlewares);
    }

    /**
     * Registers a POST route.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     * @return Router
     */
    public function post($path, $controller, $middlewares = array()): Router
    {
        return $this->route('POST', $path, $controller, $middlewares);
    }

    /**
     * Registers a PUT route.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     * @return Router
     */
    public function put($path, $controller, $middlewares = array()): Router
    {
        return $this->route('PUT', $path, $controller, $middlewares);
    }

    /**
     * Registers a PATCH route.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     * @return Router
     */
    public function patch($path, $controller, $middlewares = array()): Router
    {
        return $this->route('PATCH', $path, $controller, $middlewares);
    }

    /**
     * Registers a DELETE route.
     *
     * @param string $path The URI path for the route.
     * @param string $controller The controller and method in 'Controller@method' format.
     * @param array $middlewares An optional array of middleware classes for this route.
     * @return Router
     */
    public function delete($path, $controller, $middlewares = array()): Router
    {
        return $this->route('DELETE', $path, $controller, $middlewares);
    }

    /**
     * Registers all methods from a list of controllers that use routing attributes.
     *
     * @param array $controllers A list of controller class names.
     * @return void
     */
    public function registerControllers(array $controllers): void
    {
        foreach($controllers as $controller) {
            $classRef = new \ReflectionClass($controller);

            foreach ($classRef->getMethods() as $method) {
                $methodRef = new \ReflectionMethod($method->class, $method->name);

                foreach ($methodRef->getAttributes() as $attribute) {
                    $args = $attribute->getArguments();

                    if ($attribute->getName() === 'Lalaz\Core\Route') {
                        $this->map($args[0], $args[1], $controller, $method->name);
                    }
                }
            }
        }
    }

    /**
     * Dispatches the current HTTP request to the appropriate route.
     *
     * @param string $method The HTTP method of the request.
     * @param string $path The URI path of the request.
     * @return void
     */
    public function dispatch($method, $path): void
    {
        if (str_contains($path, "/public/")) {
            return;
        }

        $path = $this->removeQueryString($path);

        $matchRouteAndMethod = function($route, $path, &$params, $method) {
            return $this->matchPath($route['path'], $path, $params)
                && $route['method'] === strtoupper($method);
        };

        foreach ($this->routes as $route) {
            $params = array();

            if ($matchRouteAndMethod($route, $path, $params, $method)) {
                $pathParams = [];

                foreach ($route['params'] as $index => $paramName) {
                    $pathParams[$paramName] = $params[$index];
                }

                $middlewares = $route['middlewares'];
                $class = $route['controller'];
                $function = $route['function'];

                $req = new Request($pathParams);
                $res = new Response();

                foreach (array_merge($this->middlewares, $middlewares) as $middleware) {
                    $handler = new $middleware();
                    $handler->handle($req, $res);
                }

                $controllerInstance = new $class;
                $controllerInstance->callAction($function, [$req, $res]);

                return;
            }
        }

        View::renderNotFound();
    }

    /**
     * Finds a controller action based on the given action name.
     *
     * @param string $action The action name to find.
     * @return mixed The found action, or an empty array if not found.
     */
    public function find(string $action): mixed
    {
        return [];
    }

    /**
     * Removes the query string from the URL, leaving only the path.
     *
     * @param string $url The URL to clean.
     * @return string The URL without the query string.
     */
    private function removeQueryString($url): string
    {
        $url_components = parse_url($url);
        return $url_components['path'];
    }

    /**
     * Looks up the fully qualified class name of a controller.
     *
     * @param string $controllerName The short name of the controller.
     * @return string|false The fully qualified class name, or false if not found.
     */
    private function controllerLookup($controllerName): string | false
    {
        foreach (['App\\Controllers'] as $namespace) {
            $className = "${namespace}\\${controllerName}";

            if (class_exists($className)) {
                return $className;
            }
        }

        return false;
    }

    /**
     * Maps a route to a controller action and stores it in the routes array.
     *
     * @param string $method The HTTP method for the route.
     * @param string $path The URI path for the route.
     * @param string $controller The controller class name.
     * @param string $function The method name in the controller to call.
     * @param array $middlewares An optional array of middleware classes.
     * @return void
     */
    private function map($method, $path, $controller, $function, $middlewares = array()): void
    {
        $path = $this->normalizePath($path);

        $route = [
            'path' => $path,
            'method' => strtoupper($method),
            'controller' => $controller,
            'function' => $function,
            'middlewares' => $middlewares,
            'params' => $this->extractParams($path)
        ];

        $this->routes[] = $route;
    }

    /**
     * Normalizes the given URI path by trimming and cleaning it.
     *
     * @param string $path The URI path to normalize.
     * @return string The normalized URI path.
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        $path = "/{$path}";
        $path = preg_replace('#[/]{2,}#', '/', $path);
        return $path;
    }

    /**
     * Extracts parameters from the URI path.
     *
     * @param string $path The URI path to extract parameters from.
     * @return array An array of parameter names.
     */
    private function extractParams(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        return $matches[1];
    }

    /**
     * Matches the request path to the route path and extracts parameters.
     *
     * @param string $routePath The route path to match.
     * @param string $requestPath The request path to compare.
     * @param array &$params The parameters extracted from the route.
     * @return bool True if the paths match, false otherwise.
     */
    private function matchPath($routePath, $requestPath, &$params): bool
    {
        $routeRegex = preg_replace('/\{\w+\}/', '([^/]+)', $routePath);
        $routeRegex = '#^' . $routeRegex . '$#';

        if (preg_match($routeRegex, $requestPath, $matches)) {
            array_shift($matches);
            $params = $matches;
            return true;
        }

        return false;
    }
}
