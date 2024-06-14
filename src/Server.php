<?php declare(strict_types=1);

namespace Lalaz;

class Server
{
    private $router;

    public function __construct() {
        $this->router = new Router();
    }

    public function registerControllers($controllers = []): void {
        $this->router->registerControllers($controllers);
    }

    public function get($path, $controller): Server {
        $this->router->get($path, $controller);
        return $this;
    }

    public function post($path, $controller): Server {
        $this->router->post($path, $controller);
        return $this;
    }

    public function put($path, $controller): Server {
        $this->router->put($path, $controller);
        return $this;
    }

    public function patch($path, $controller): Server {
        $this->router->patch($path, $controller);
        return $this;
    }

    public function delete($path, $controller): Server {
        $this->router->delete($path, $controller);
        return $this;
    }

    public function run(): void {
        $this->router->dispatch(
            $_SERVER['REQUEST_METHOD'], 
            $_SERVER['REQUEST_URI']
        );
    }
}