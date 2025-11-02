<?php declare(strict_types=1);

use Lalaz\Security\Middleware\PermissionMiddleware;
use Lalaz\Security\Authorizable;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Http\SessionManager;

describe('PermissionMiddleware', function () {
    beforeEach(function () {
        SessionManager::destroy();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    });

    describe('constructor', function () {
        it('can be instantiated without required permissions', function () {
            $middleware = new PermissionMiddleware();

            expect($middleware)->toBeInstanceOf(PermissionMiddleware::class);
        });

        it('can be instantiated with required permissions', function () {
            $middleware = new PermissionMiddleware(['read', 'write', 'delete']);

            expect($middleware)->toBeInstanceOf(PermissionMiddleware::class);
        });

        it('accepts empty array of permissions', function () {
            $middleware = new PermissionMiddleware([]);

            expect($middleware)->toBeInstanceOf(PermissionMiddleware::class);
        });

        it('accepts single permission', function () {
            $middleware = new PermissionMiddleware(['admin']);

            expect($middleware)->toBeInstanceOf(PermissionMiddleware::class);
        });
    });

    describe('handle() - with authorized user', function () {
        it('allows access when user has required permission', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['read', 'write', 'delete'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['read']);
            $request = new Request();
            $response = new Response();

            // Should not redirect
            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('allows access when user has any of the required permissions', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['write', 'edit'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['read', 'write', 'delete']);
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('allows access when no permissions required', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['read'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware([]);
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });
    });

    describe('handle() - without authorized user', function () {
        it('redirects to /unauthorized when user does not have required permission', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['read'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['admin', 'delete']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;
                public string $redirectUrl = '';

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                    $this->redirectUrl = $url;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
            expect($response->redirectUrl)->toBe('/unauthorized');
        });

        it('redirects when user has no permissions', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
        });

        it('redirects when user is null', function () {
            SessionManager::set('__luser', null);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
        });

        it('redirects when user does not implement Authorizable', function () {
            $user = (object)['id' => 1, 'name' => 'Test'];
            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
        });
    });

    describe('permission checking', function () {
        it('is case sensitive for permission names', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['Read']; // capital R
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['read']); // lowercase r
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            // Should redirect because 'Read' !== 'read'
            expect($response->redirectCalled)->toBeTrue();
        });

        it('checks using hasAnyPermission method', function () {
            $user = new class {
                use Authorizable;
                public bool $hasAnyPermissionCalled = false;
                public array $checkedPermissions = [];

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['write'];
                }

                public function hasAnyPermission(array $permissions): bool
                {
                    $this->hasAnyPermissionCalled = true;
                    $this->checkedPermissions = $permissions;
                    return (bool) array_intersect($permissions, $this->getPermissions());
                }
            };

            SessionManager::set('__luser', $user);

            $requiredPermissions = ['read', 'write'];
            $middleware = new PermissionMiddleware($requiredPermissions);
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect($user->hasAnyPermissionCalled)->toBeTrue();
            expect($user->checkedPermissions)->toBe($requiredPermissions);
        });
    });

    describe('session key usage', function () {
        it('uses "__luser" as session key', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['admin'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();
            $response = new Response();

            // Should not redirect
            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('does not check permissions with different session key', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['admin'];
                }
            };

            SessionManager::set('user', $user);
            SessionManager::set('current_user', $user);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            // Should redirect because '__luser' is not set
            expect($response->redirectCalled)->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('handles false as user', function () {
            SessionManager::set('__luser', false);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
        });

        it('handles empty string as user', function () {
            SessionManager::set('__luser', '');

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
        });

        it('handles zero as user', function () {
            SessionManager::set('__luser', 0);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public bool $redirectCalled = false;

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
        });

        it('redirects to exactly "/unauthorized"', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['admin']);
            $request = new Request();

            $response = new class extends Response {
                public string $redirectUrl = '';

                public function redirect(string $url): void
                {
                    $this->redirectUrl = $url;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectUrl)->toBe('/unauthorized');
        });
    });

    describe('multiple permissions', function () {
        it('accepts user with first permission', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['read'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['read', 'write', 'delete']);
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('accepts user with middle permission', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['write'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['read', 'write', 'delete']);
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('accepts user with last permission', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['delete'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['read', 'write', 'delete']);
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('accepts user with multiple matching permissions', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['read', 'write'];
                }
            };

            SessionManager::set('__luser', $user);

            $middleware = new PermissionMiddleware(['read', 'delete']);
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });
    });
});
