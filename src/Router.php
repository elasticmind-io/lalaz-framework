<?php declare(strict_types=1);

namespace Lalaz;

class Router 
{
    protected $routes = [];

    public function route($method, $path, $controller): Router {
        [$controllerName, $function] = explode('@', $controller);
        $controllerClassName = $this->controllerLookup($controllerName);

        if (!$controllerClassName) {
            die("Controller ${controllerName} was not found!");
        }

        $this->map($method, $path, $controllerClassName, $function);

        return $this;
    }

    public function get($path, $controller): Router {
        return $this->route('GET', $path, $controller);
    }

    public function post($path, $controller): Router {
        return $this->route('POST', $path, $controller);
    }

    public function put($path, $controller): Router {
        return $this->route('PUT', $path, $controller);
    }

    public function patch($path, $controller): Router {
        return $this->route('PATCH', $path, $controller);
    }

    public function delete($path, $controller): Router {
        return $this->route('DELETE', $path, $controller);
    }

    public function registerControllers(array $controllers) {
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

    public function dispatch($method, $path): void {
        if (str_contains($path, "/public/")) {
            return;
        }

        foreach ($this->routes as $route) {
            $params = [];
            
            if (
              $this->matchPath($route['path'], $path, $params) &&
              $route['method'] === strtoupper($method)
            ) {
                $pathParams = [];
                
                foreach ($route['params'] as $index => $paramName) {
                    $pathParams[$paramName] = $params[$index];
                }

                $class = $route['controller'];
                $function = $route['function'];

                $controllerInstance = new $class;

                $controllerInstance->{$function}(
                    new HttpRequest($pathParams), 
                    new HttpResponse(),
                );
                
                return;
            }
        }

        View::renderNotFound();
    }

    private function controllerLookup($controllerName): string | false {
        foreach (APP_CONTROLLERS_NAMESPACES as $namespace) {
            $className = "${namespace}\\${controllerName}";

            if (class_exists($className)) {
                return $className;
            }
        }

        return false;
    }

    private function map($method, $path, $controller, $function): void {
        $path = $this->normalizePath($path);
        
        $route = [
            'path' => $path,
            'method' => strtoupper($method),
            'controller' => $controller,
            'function' => $function,
            'middlewares' => [],
            'params' => $this->extractParams($path)
        ];

        $this->routes[] = $route;
    }

    private function normalizePath(string $path): string { 
        $path = trim($path, '/'); 
        $path = "/{$path}"; 
        $path = preg_replace('#[/]{2,}#', '/', $path);
        return $path;
    }

    private function extractParams(string $path): array {
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        return $matches[1];
    }

    private function matchPath($routePath, $requestPath, &$params) {
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
