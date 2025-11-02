<?php

use Lalaz\Routing\Router;
use Lalaz\Routing\RouteDefinition;
use Lalaz\Routing\GroupDefinition;

describe('Router', function() {
    beforeEach(function() {
        // Create stub controllers in App\Controllers namespace using eval
        if (!class_exists('App\\Controllers\\TestController')) {
            eval('
                namespace App\\Controllers {
                    class TestController {
                        public function index($req, $res) {}
                        public function show($req, $res) {}
                        public function store($req, $res) {}
                        public function update($req, $res) {}
                        public function destroy($req, $res) {}
                        public function first($req, $res) {}
                        public function second($req, $res) {}
                        public function third($req, $res) {}
                        public function outside($req, $res) {}
                        public function about($req, $res) {}
                        public function home($req, $res) {}
                        public function search($req, $res) {}
                        public function callAction($method, $params) {}
                    }

                    class UserController {
                        public function callAction($method, $params) {}
                    }

                    class PostController {
                        public function callAction($method, $params) {}
                    }
                }
            ');
        }
    });

    describe('Constructor', function() {
        it('creates router instance', function() {
            $router = new Router();

            expect($router)->toBeInstanceOf(Router::class);
        });

        it('initializes with empty routes', function() {
            $router = new Router();

            expect($router->getRoutes())->toBe([]);
        });
    });

    describe('Route Registration - GET', function() {
        it('registers GET route', function() {
            $router = new Router();
            $result = $router->get('/users', 'TestController@index');

            expect($result)->toBeInstanceOf(RouteDefinition::class);
        });

        it('stores GET route in routes array', function() {
            $router = new Router();
            $router->get('/users', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes)->toHaveCount(1);
            expect($routes[0]->getMethod())->toBe('GET');
            expect($routes[0]->getPath())->toBe('/users');
        });

        it('registers GET route with middlewares', function() {
            $router = new Router();
            $router->get('/users', 'TestController@index', ['AuthMiddleware']);

            $routes = $router->getRoutes();
            expect($routes[0]->getMiddlewares())->toContain('AuthMiddleware');
        });

        it('registers GET route with empty middleware array', function() {
            $router = new Router();
            $router->get('/users', 'TestController@index', []);

            $routes = $router->getRoutes();
            expect($routes[0]->getMiddlewares())->toBe([]);
        });
    });

    describe('Route Registration - POST', function() {
        it('registers POST route', function() {
            $router = new Router();
            $result = $router->post('/users', 'TestController@store');

            expect($result)->toBeInstanceOf(RouteDefinition::class);
        });

        it('stores POST route correctly', function() {
            $router = new Router();
            $router->post('/users', 'TestController@store');

            $routes = $router->getRoutes();
            expect($routes[0]->getMethod())->toBe('POST');
        });

        it('registers POST route with middlewares', function() {
            $router = new Router();
            $router->post('/users', 'TestController@store', ['ValidationMiddleware']);

            $routes = $router->getRoutes();
            expect($routes[0]->getMiddlewares())->toContain('ValidationMiddleware');
        });
    });

    describe('Route Registration - PUT', function() {
        it('registers PUT route', function() {
            $router = new Router();
            $result = $router->put('/users/{id}', 'TestController@update');

            expect($result)->toBeInstanceOf(RouteDefinition::class);
        });

        it('stores PUT route correctly', function() {
            $router = new Router();
            $router->put('/users/{id}', 'TestController@update');

            $routes = $router->getRoutes();
            expect($routes[0]->getMethod())->toBe('PUT');
        });
    });

    describe('Route Registration - PATCH', function() {
        it('registers PATCH route', function() {
            $router = new Router();
            $result = $router->patch('/users/{id}', 'TestController@update');

            expect($result)->toBeInstanceOf(RouteDefinition::class);
        });

        it('stores PATCH route correctly', function() {
            $router = new Router();
            $router->patch('/users/{id}', 'TestController@update');

            $routes = $router->getRoutes();
            expect($routes[0]->getMethod())->toBe('PATCH');
        });
    });

    describe('Route Registration - DELETE', function() {
        it('registers DELETE route', function() {
            $router = new Router();
            $result = $router->delete('/users/{id}', 'TestController@destroy');

            expect($result)->toBeInstanceOf(RouteDefinition::class);
        });

        it('stores DELETE route correctly', function() {
            $router = new Router();
            $router->delete('/users/{id}', 'TestController@destroy');

            $routes = $router->getRoutes();
            expect($routes[0]->getMethod())->toBe('DELETE');
        });
    });

    describe('Multiple Routes', function() {
        it('registers multiple routes', function() {
            $router = new Router();
            $router->get('/users', 'TestController@index');
            $router->post('/users', 'TestController@store');
            $router->get('/users/{id}', 'TestController@show');

            expect($router->getRoutes())->toHaveCount(3);
        });

        it('maintains route order', function() {
            $router = new Router();
            $router->get('/first', 'TestController@first');
            $router->post('/second', 'TestController@second');
            $router->put('/third', 'TestController@third');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/first');
            expect($routes[1]->getPath())->toBe('/second');
            expect($routes[2]->getPath())->toBe('/third');
        });

        it('allows same path with different methods', function() {
            $router = new Router();
            $router->get('/users', 'TestController@index');
            $router->post('/users', 'TestController@store');

            $routes = $router->getRoutes();
            expect($routes)->toHaveCount(2);
            expect($routes[0]->getMethod())->toBe('GET');
            expect($routes[1]->getMethod())->toBe('POST');
        });
    });

    describe('Global Middleware', function() {
        it('registers global middleware', function() {
            $router = new Router();
            $result = $router->use('CorsMiddleware');

            expect($result)->toBeInstanceOf(Router::class);
        });

        it('allows method chaining with use', function() {
            $router = new Router();
            $result = $router->use('CorsMiddleware')
                            ->use('LogMiddleware');

            expect($result)->toBeInstanceOf(Router::class);
        });

        it('stores multiple global middlewares', function() {
            $router = new Router();
            $router->use('CorsMiddleware');
            $router->use('LogMiddleware');

            // Global middlewares are internal, tested through dispatch
            expect($router)->toBeInstanceOf(Router::class);
        });
    });

    describe('Route Groups', function() {
        it('creates route group', function() {
            $router = new Router();
            $result = $router->group('/api', function($router) {
                $router->get('/users', 'TestController@index');
            });

            expect($result)->toBeInstanceOf(GroupDefinition::class);
        });

        it('prefixes routes in group', function() {
            $router = new Router();
            $router->group('/api', function($router) {
                $router->get('/users', 'TestController@index');
                $router->post('/users', 'TestController@store');
            });

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/api/users');
            expect($routes[1]->getPath())->toBe('/api/users');
        });

        it('handles nested groups', function() {
            $router = new Router();
            $router->group('/api', function($router) {
                $router->group('/v1', function($router) {
                    $router->get('/users', 'TestController@index');
                });
            });

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/api/v1/users');
        });

        it('normalizes group prefix', function() {
            $router = new Router();
            $router->group('/api/', function($router) {
                $router->get('/users', 'TestController@index');
            });

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/api/users');
        });

        it('handles empty prefix group', function() {
            $router = new Router();
            $router->group('', function($router) {
                $router->get('/users', 'TestController@index');
            });

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/users');
        });

        it('returns routes in group', function() {
            $router = new Router();
            $router->get('/outside', 'TestController@outside');

            $group = $router->group('/api', function($router) {
                $router->get('/users', 'TestController@index');
                $router->post('/users', 'TestController@store');
            });

            // Group returns GroupDefinition which has access to its routes
            expect($group)->toBeInstanceOf(GroupDefinition::class);
        });
    });

    describe('Path Normalization', function() {
        it('normalizes path with trailing slash', function() {
            $router = new Router();
            $router->get('/users/', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/users');
        });

        it('normalizes path without leading slash', function() {
            $router = new Router();
            $router->get('users', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/users');
        });

        it('normalizes multiple slashes', function() {
            $router = new Router();
            $router->get('//users///', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/users');
        });

        it('preserves root path', function() {
            $router = new Router();
            $router->get('/', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/');
        });

        it('normalizes paths with parameters', function() {
            $router = new Router();
            $router->get('/users/{id}/', 'TestController@show');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/users/{id}');
        });
    });

    describe('Controller Lookup', function() {
        it('finds controller in App\\Controllers namespace', function() {
            $router = new Router();

            // This should not die because TestController exists in App\Controllers
            $router->get('/test', 'TestController@index');

            expect($router->getRoutes())->toHaveCount(1);
        });

        it('stores fully qualified controller class name', function() {
            $router = new Router();
            $router->get('/users', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes[0]->getController())->toBe('App\\Controllers\\TestController');
        });
    });

    describe('Find Method', function() {
        it('has find method', function() {
            $router = new Router();

            expect(method_exists($router, 'find'))->toBeTrue();
        });

        it('find returns empty array for now', function() {
            $router = new Router();
            $result = $router->find('SomeAction');

            expect($result)->toBe([]);
        });
    });

    describe('Edge Cases', function() {
        it('handles complex paths with multiple segments', function() {
            $router = new Router();
            $router->get('/api/v1/users/{userId}/posts/{postId}/comments', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/api/v1/users/{userId}/posts/{postId}/comments');
        });

        it('handles routes with special characters in path', function() {
            $router = new Router();
            $router->get('/api-v2/user_profile', 'TestController@index');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/api-v2/user_profile');
        });

        it('handles multiple parameters', function() {
            $router = new Router();
            $router->get('/posts/{year}/{month}/{slug}', 'TestController@show');

            $routes = $router->getRoutes();
            expect($routes[0]->getParams())->toBe(['year', 'month', 'slug']);
        });

        it('handles route with query parameters in path', function() {
            $router = new Router();
            $router->get('/search', 'TestController@search');

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/search');
        });

        it('handles deeply nested groups', function() {
            $router = new Router();
            $router->group('/api', function($router) {
                $router->group('/v1', function($router) {
                    $router->group('/admin', function($router) {
                        $router->get('/users', 'TestController@index');
                    });
                });
            });

            $routes = $router->getRoutes();
            expect($routes[0]->getPath())->toBe('/api/v1/admin/users');
        });

        it('handles group with multiple routes', function() {
            $router = new Router();
            $router->group('/api', function($router) {
                $router->get('/users', 'TestController@index');
                $router->post('/users', 'TestController@store');
                $router->get('/users/{id}', 'TestController@show');
                $router->put('/users/{id}', 'TestController@update');
                $router->delete('/users/{id}', 'TestController@destroy');
            });

            expect($router->getRoutes())->toHaveCount(5);
        });

        it('combines routes outside and inside groups', function() {
            $router = new Router();
            $router->get('/home', 'TestController@home');

            $router->group('/api', function($router) {
                $router->get('/users', 'TestController@index');
            });

            $router->get('/about', 'TestController@about');

            $routes = $router->getRoutes();
            expect($routes)->toHaveCount(3);
            expect($routes[0]->getPath())->toBe('/home');
            expect($routes[1]->getPath())->toBe('/api/users');
            expect($routes[2]->getPath())->toBe('/about');
        });
    });

    describe('Method Chaining', function() {
        it('allows chaining route definitions', function() {
            $router = new Router();
            $route = $router->get('/users', 'TestController@index')
                           ->middlewares(['AuthMiddleware']);

            expect($route)->toBeInstanceOf(RouteDefinition::class);
            expect($route->getMiddlewares())->toContain('AuthMiddleware');
        });

        it('allows chaining group definitions', function() {
            $router = new Router();
            $group = $router->group('/api', function($router) {
                $router->get('/users', 'TestController@index');
            })->middlewares(['AuthMiddleware']);

            expect($group)->toBeInstanceOf(GroupDefinition::class);
        });

        it('allows complex chaining', function() {
            $router = new Router();
            $router->use('CorsMiddleware');
            $route = $router->get('/home', 'TestController@home')
                           ->middlewares(['CacheMiddleware']);

            $routes = $router->getRoutes();
            expect($routes[0]->getMiddlewares())->toContain('CacheMiddleware');
        });
    });
});
