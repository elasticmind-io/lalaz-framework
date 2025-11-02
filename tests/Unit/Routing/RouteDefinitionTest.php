<?php

use Lalaz\Routing\RouteDefinition;
use Lalaz\Security\Middleware\AuthenticationMiddleware;
use Lalaz\Security\Middleware\AuthorizationMiddleware;
use Lalaz\Security\Middleware\PermissionMiddleware;

describe('RouteDefinition', function() {
    describe('Constructor', function() {
        it('creates instance with required parameters', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);

            expect($route)->toBeInstanceOf(RouteDefinition::class);
        });

        it('converts method to uppercase', function() {
            $route = new RouteDefinition('get', '/users', 'UserController', 'index', []);

            expect($route->getMethod())->toBe('GET');
        });

        it('stores path correctly', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);

            expect($route->getPath())->toBe('/users');
        });

        it('stores controller correctly', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);

            expect($route->getController())->toBe('UserController');
        });

        it('stores function correctly', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);

            expect($route->getFunction())->toBe('index');
        });

        it('stores middlewares correctly', function() {
            $middlewares = ['AuthMiddleware', 'LogMiddleware'];
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', $middlewares);

            expect($route->getMiddlewares())->toBe($middlewares);
        });
    });

    describe('Parameter Extraction', function() {
        it('extracts single parameter from path', function() {
            $route = new RouteDefinition('GET', '/users/{id}', 'UserController', 'show', []);

            expect($route->getParams())->toBe(['id']);
        });

        it('extracts multiple parameters from path', function() {
            $route = new RouteDefinition('GET', '/users/{userId}/posts/{postId}', 'PostController', 'show', []);

            expect($route->getParams())->toBe(['userId', 'postId']);
        });

        it('returns empty array when no parameters in path', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);

            expect($route->getParams())->toBe([]);
        });

        it('handles parameters with underscores', function() {
            $route = new RouteDefinition('GET', '/users/{user_id}', 'UserController', 'show', []);

            expect($route->getParams())->toBe(['user_id']);
        });

        it('handles parameters with numbers', function() {
            $route = new RouteDefinition('GET', '/items/{id1}/compare/{id2}', 'ItemController', 'compare', []);

            expect($route->getParams())->toBe(['id1', 'id2']);
        });
    });

    describe('HTTP Methods', function() {
        it('handles GET method', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);

            expect($route->getMethod())->toBe('GET');
        });

        it('handles POST method', function() {
            $route = new RouteDefinition('POST', '/users', 'UserController', 'store', []);

            expect($route->getMethod())->toBe('POST');
        });

        it('handles PUT method', function() {
            $route = new RouteDefinition('PUT', '/users/{id}', 'UserController', 'update', []);

            expect($route->getMethod())->toBe('PUT');
        });

        it('handles PATCH method', function() {
            $route = new RouteDefinition('PATCH', '/users/{id}', 'UserController', 'update', []);

            expect($route->getMethod())->toBe('PATCH');
        });

        it('handles DELETE method', function() {
            $route = new RouteDefinition('DELETE', '/users/{id}', 'UserController', 'destroy', []);

            expect($route->getMethod())->toBe('DELETE');
        });

        it('normalizes lowercase methods to uppercase', function() {
            $route = new RouteDefinition('post', '/users', 'UserController', 'store', []);

            expect($route->getMethod())->toBe('POST');
        });
    });

    describe('Middleware Management', function() {
        it('adds middlewares via middlewares method', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);
            $result = $route->middlewares(['AuthMiddleware']);

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            expect($route->getMiddlewares())->toContain('AuthMiddleware');
        });

        it('merges new middlewares with existing ones', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', ['LogMiddleware']);
            $route->middlewares(['AuthMiddleware', 'RateLimitMiddleware']);

            $middlewares = $route->getMiddlewares();
            expect($middlewares)->toHaveCount(3);
            expect($middlewares)->toContain('LogMiddleware');
            expect($middlewares)->toContain('AuthMiddleware');
            expect($middlewares)->toContain('RateLimitMiddleware');
        });

        it('allows method chaining', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);
            $result = $route->middlewares(['AuthMiddleware'])
                           ->middlewares(['LogMiddleware']);

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            expect($route->getMiddlewares())->toHaveCount(2);
        });
    });

    describe('Authentication Middleware', function() {
        it('adds authentication middleware', function() {
            $route = new RouteDefinition('GET', '/dashboard', 'DashboardController', 'index', []);
            $result = $route->useAuthentication();

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            $middlewares = $route->getMiddlewares();
            expect($middlewares)->toHaveCount(1);
            expect($middlewares[0])->toBeInstanceOf(AuthenticationMiddleware::class);
        });

        it('adds authentication middleware with login URL', function() {
            $route = new RouteDefinition('GET', '/dashboard', 'DashboardController', 'index', []);
            $route->useAuthentication('/login');

            $middlewares = $route->getMiddlewares();
            expect($middlewares[0])->toBeInstanceOf(AuthenticationMiddleware::class);
        });

        it('allows method chaining with authentication', function() {
            $route = new RouteDefinition('GET', '/dashboard', 'DashboardController', 'index', []);
            $result = $route->useAuthentication()
                           ->middlewares(['LogMiddleware']);

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            expect($route->getMiddlewares())->toHaveCount(2);
        });
    });

    describe('Authorization Middleware', function() {
        it('adds authorization middleware', function() {
            $route = new RouteDefinition('GET', '/admin', 'AdminController', 'index', []);
            $result = $route->useAuthorization(['admin']);

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            $middlewares = $route->getMiddlewares();
            expect($middlewares)->toHaveCount(1);
            expect($middlewares[0])->toBeInstanceOf(AuthorizationMiddleware::class);
        });

        it('adds authorization middleware with multiple roles', function() {
            $route = new RouteDefinition('GET', '/admin', 'AdminController', 'index', []);
            $route->useAuthorization(['admin', 'moderator']);

            $middlewares = $route->getMiddlewares();
            expect($middlewares[0])->toBeInstanceOf(AuthorizationMiddleware::class);
        });

        it('allows method chaining with authorization', function() {
            $route = new RouteDefinition('GET', '/admin', 'AdminController', 'index', []);
            $result = $route->useAuthentication()
                           ->useAuthorization(['admin']);

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            expect($route->getMiddlewares())->toHaveCount(2);
        });
    });

    describe('Permission Middleware', function() {
        it('adds permission middleware', function() {
            $route = new RouteDefinition('POST', '/posts', 'PostController', 'create', []);
            $result = $route->usePermissions(['create_post']);

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            $middlewares = $route->getMiddlewares();
            expect($middlewares)->toHaveCount(1);
            expect($middlewares[0])->toBeInstanceOf(PermissionMiddleware::class);
        });

        it('adds permission middleware with multiple permissions', function() {
            $route = new RouteDefinition('POST', '/posts', 'PostController', 'create', []);
            $route->usePermissions(['create_post', 'publish_post']);

            $middlewares = $route->getMiddlewares();
            expect($middlewares[0])->toBeInstanceOf(PermissionMiddleware::class);
        });

        it('allows method chaining with permissions', function() {
            $route = new RouteDefinition('POST', '/posts', 'PostController', 'create', []);
            $result = $route->useAuthentication()
                           ->usePermissions(['create_post']);

            expect($result)->toBeInstanceOf(RouteDefinition::class);
            expect($route->getMiddlewares())->toHaveCount(2);
        });
    });

    describe('Complex Middleware Chains', function() {
        it('combines authentication, authorization and custom middleware', function() {
            $route = new RouteDefinition('POST', '/admin/posts', 'AdminPostController', 'create', []);
            $route->middlewares(['LogMiddleware'])
                  ->useAuthentication('/login')
                  ->useAuthorization(['admin'])
                  ->middlewares(['RateLimitMiddleware']);

            $middlewares = $route->getMiddlewares();
            expect($middlewares)->toHaveCount(4);
            expect($middlewares[0])->toBe('LogMiddleware');
            expect($middlewares[1])->toBeInstanceOf(AuthenticationMiddleware::class);
            expect($middlewares[2])->toBeInstanceOf(AuthorizationMiddleware::class);
            expect($middlewares[3])->toBe('RateLimitMiddleware');
        });

        it('combines all security middlewares', function() {
            $route = new RouteDefinition('DELETE', '/posts/{id}', 'PostController', 'destroy', []);
            $route->useAuthentication()
                  ->useAuthorization(['admin', 'moderator'])
                  ->usePermissions(['delete_post']);

            $middlewares = $route->getMiddlewares();
            expect($middlewares)->toHaveCount(3);
            expect($middlewares[0])->toBeInstanceOf(AuthenticationMiddleware::class);
            expect($middlewares[1])->toBeInstanceOf(AuthorizationMiddleware::class);
            expect($middlewares[2])->toBeInstanceOf(PermissionMiddleware::class);
        });
    });

    describe('Edge Cases', function() {
        it('handles empty middleware array', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'index', []);

            expect($route->getMiddlewares())->toBe([]);
        });

        it('handles complex paths', function() {
            $route = new RouteDefinition('GET', '/api/v1/users/{id}/posts/{postId}/comments', 'CommentController', 'index', []);

            expect($route->getPath())->toBe('/api/v1/users/{id}/posts/{postId}/comments');
            expect($route->getParams())->toBe(['id', 'postId']);
        });

        it('handles root path', function() {
            $route = new RouteDefinition('GET', '/', 'HomeController', 'index', []);

            expect($route->getPath())->toBe('/');
            expect($route->getParams())->toBe([]);
        });

        it('handles mixed case method names', function() {
            $route = new RouteDefinition('PoSt', '/users', 'UserController', 'store', []);

            expect($route->getMethod())->toBe('POST');
        });

        it('handles controller with namespace', function() {
            $route = new RouteDefinition('GET', '/users', 'App\\Controllers\\UserController', 'index', []);

            expect($route->getController())->toBe('App\\Controllers\\UserController');
        });

        it('handles function names with underscores', function() {
            $route = new RouteDefinition('GET', '/users', 'UserController', 'get_all_users', []);

            expect($route->getFunction())->toBe('get_all_users');
        });

        it('handles special characters in path', function() {
            $route = new RouteDefinition('GET', '/api-v2/user_profile', 'UserController', 'profile', []);

            expect($route->getPath())->toBe('/api-v2/user_profile');
        });
    });
});
