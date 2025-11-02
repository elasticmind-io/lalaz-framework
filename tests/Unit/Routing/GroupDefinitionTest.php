<?php

use Lalaz\Routing\GroupDefinition;
use Lalaz\Routing\RouteDefinition;
use Lalaz\Security\Middleware\AuthenticationMiddleware;
use Lalaz\Security\Middleware\AuthorizationMiddleware;

describe('GroupDefinition', function() {
    describe('Constructor', function() {
        it('creates instance with routes and prefix', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
                new RouteDefinition('POST', '/users', 'UserController', 'store', []),
            ];

            $group = new GroupDefinition($routes, '/api');

            expect($group)->toBeInstanceOf(GroupDefinition::class);
        });
    });

    describe('Single Middleware', function() {
        it('adds middleware to all routes in group', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
                new RouteDefinition('POST', '/users', 'UserController', 'store', []),
            ];

            $group = new GroupDefinition($routes, '/api');
            $result = $group->middleware('AuthMiddleware');

            expect($result)->toBeInstanceOf(GroupDefinition::class);
            expect($routes[0]->getMiddlewares())->toContain('AuthMiddleware');
            expect($routes[1]->getMiddlewares())->toContain('AuthMiddleware');
        });

        it('allows method chaining', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/api');
            $result = $group->middleware('AuthMiddleware')
                           ->middleware('LogMiddleware');

            expect($result)->toBeInstanceOf(GroupDefinition::class);
            expect($routes[0]->getMiddlewares())->toHaveCount(2);
        });
    });

    describe('Multiple Middlewares', function() {
        it('adds multiple middlewares to all routes', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
                new RouteDefinition('POST', '/users', 'UserController', 'store', []),
            ];

            $group = new GroupDefinition($routes, '/api');
            $result = $group->middlewares(['AuthMiddleware', 'LogMiddleware']);

            expect($result)->toBeInstanceOf(GroupDefinition::class);

            foreach ($routes as $route) {
                expect($route->getMiddlewares())->toHaveCount(2);
                expect($route->getMiddlewares())->toContain('AuthMiddleware');
                expect($route->getMiddlewares())->toContain('LogMiddleware');
            }
        });

        it('merges with existing route middlewares', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', ['ExistingMiddleware']),
            ];

            $group = new GroupDefinition($routes, '/api');
            $group->middlewares(['AuthMiddleware']);

            $middlewares = $routes[0]->getMiddlewares();
            expect($middlewares)->toHaveCount(2);
            expect($middlewares)->toContain('ExistingMiddleware');
            expect($middlewares)->toContain('AuthMiddleware');
        });

        it('handles empty middleware array', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/api');
            $result = $group->middlewares([]);

            expect($result)->toBeInstanceOf(GroupDefinition::class);
            expect($routes[0]->getMiddlewares())->toBe([]);
        });
    });

    describe('Authentication Middleware', function() {
        it('adds authentication to all routes in group', function() {
            $routes = [
                new RouteDefinition('GET', '/dashboard', 'DashboardController', 'index', []),
                new RouteDefinition('GET', '/profile', 'ProfileController', 'show', []),
            ];

            $group = new GroupDefinition($routes, '/app');
            $result = $group->useAuthentication();

            expect($result)->toBeInstanceOf(GroupDefinition::class);

            foreach ($routes as $route) {
                $middlewares = $route->getMiddlewares();
                expect($middlewares)->toHaveCount(1);
                expect($middlewares[0])->toBeInstanceOf(AuthenticationMiddleware::class);
            }
        });

        it('adds authentication with login URL', function() {
            $routes = [
                new RouteDefinition('GET', '/dashboard', 'DashboardController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/app');
            $group->useAuthentication('/login');

            $middlewares = $routes[0]->getMiddlewares();
            expect($middlewares[0])->toBeInstanceOf(AuthenticationMiddleware::class);
        });

        it('allows method chaining with authentication', function() {
            $routes = [
                new RouteDefinition('GET', '/dashboard', 'DashboardController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/app');
            $result = $group->useAuthentication()
                           ->middlewares(['LogMiddleware']);

            expect($result)->toBeInstanceOf(GroupDefinition::class);
            expect($routes[0]->getMiddlewares())->toHaveCount(2);
        });
    });

    describe('Authorization Middleware', function() {
        it('adds authorization to all routes in group', function() {
            $routes = [
                new RouteDefinition('GET', '/admin/users', 'AdminUserController', 'index', []),
                new RouteDefinition('POST', '/admin/users', 'AdminUserController', 'store', []),
            ];

            $group = new GroupDefinition($routes, '/admin');
            $result = $group->useAuthorization(['admin']);

            expect($result)->toBeInstanceOf(GroupDefinition::class);

            foreach ($routes as $route) {
                $middlewares = $route->getMiddlewares();
                expect($middlewares)->toHaveCount(1);
                expect($middlewares[0])->toBeInstanceOf(AuthorizationMiddleware::class);
            }
        });

        it('adds authorization with multiple roles', function() {
            $routes = [
                new RouteDefinition('GET', '/admin/users', 'AdminUserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/admin');
            $group->useAuthorization(['admin', 'moderator']);

            $middlewares = $routes[0]->getMiddlewares();
            expect($middlewares[0])->toBeInstanceOf(AuthorizationMiddleware::class);
        });

        it('allows method chaining with authorization', function() {
            $routes = [
                new RouteDefinition('GET', '/admin/users', 'AdminUserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/admin');
            $result = $group->useAuthentication()
                           ->useAuthorization(['admin']);

            expect($result)->toBeInstanceOf(GroupDefinition::class);
            expect($routes[0]->getMiddlewares())->toHaveCount(2);
        });
    });

    describe('Complex Scenarios', function() {
        it('applies middlewares to group with many routes', function() {
            $routes = [];
            for ($i = 0; $i < 10; $i++) {
                $routes[] = new RouteDefinition('GET', "/route{$i}", 'Controller', 'method', []);
            }

            $group = new GroupDefinition($routes, '/api');
            $group->middlewares(['AuthMiddleware', 'LogMiddleware']);

            foreach ($routes as $route) {
                expect($route->getMiddlewares())->toHaveCount(2);
            }
        });

        it('combines authentication and authorization', function() {
            $routes = [
                new RouteDefinition('GET', '/admin/dashboard', 'AdminController', 'index', []),
                new RouteDefinition('GET', '/admin/settings', 'AdminController', 'settings', []),
            ];

            $group = new GroupDefinition($routes, '/admin');
            $group->useAuthentication('/login')
                  ->useAuthorization(['admin', 'superadmin']);

            foreach ($routes as $route) {
                $middlewares = $route->getMiddlewares();
                expect($middlewares)->toHaveCount(2);
                expect($middlewares[0])->toBeInstanceOf(AuthenticationMiddleware::class);
                expect($middlewares[1])->toBeInstanceOf(AuthorizationMiddleware::class);
            }
        });

        it('applies group middlewares on top of route middlewares', function() {
            $routes = [
                new RouteDefinition('GET', '/posts', 'PostController', 'index', ['CacheMiddleware']),
                new RouteDefinition('POST', '/posts', 'PostController', 'store', ['ValidationMiddleware']),
            ];

            $group = new GroupDefinition($routes, '/api');
            $group->middlewares(['AuthMiddleware']);

            expect($routes[0]->getMiddlewares())->toBe(['CacheMiddleware', 'AuthMiddleware']);
            expect($routes[1]->getMiddlewares())->toBe(['ValidationMiddleware', 'AuthMiddleware']);
        });

        it('chains multiple middleware applications', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/api');
            $group->middleware('FirstMiddleware')
                  ->middlewares(['SecondMiddleware', 'ThirdMiddleware'])
                  ->middleware('FourthMiddleware');

            $middlewares = $routes[0]->getMiddlewares();
            expect($middlewares)->toHaveCount(4);
            expect($middlewares[0])->toBe('FirstMiddleware');
            expect($middlewares[1])->toBe('SecondMiddleware');
            expect($middlewares[2])->toBe('ThirdMiddleware');
            expect($middlewares[3])->toBe('FourthMiddleware');
        });
    });

    describe('Edge Cases', function() {
        it('handles empty routes array', function() {
            $routes = [];

            $group = new GroupDefinition($routes, '/api');
            $result = $group->middlewares(['AuthMiddleware']);

            expect($result)->toBeInstanceOf(GroupDefinition::class);
        });

        it('handles single route', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/api');
            $group->middleware('AuthMiddleware');

            expect($routes[0]->getMiddlewares())->toContain('AuthMiddleware');
        });

        it('handles empty prefix', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '');
            $result = $group->middleware('AuthMiddleware');

            expect($result)->toBeInstanceOf(GroupDefinition::class);
        });

        it('handles complex prefix', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/api/v1/admin');
            $result = $group->middleware('AuthMiddleware');

            expect($result)->toBeInstanceOf(GroupDefinition::class);
        });

        it('modifies routes by reference', function() {
            $routes = [
                new RouteDefinition('GET', '/users', 'UserController', 'index', []),
            ];

            $group = new GroupDefinition($routes, '/api');
            $group->middleware('AuthMiddleware');

            // Routes should be modified by reference
            expect($routes[0]->getMiddlewares())->toContain('AuthMiddleware');
        });
    });
});
