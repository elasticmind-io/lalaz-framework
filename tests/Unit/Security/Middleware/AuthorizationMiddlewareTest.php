<?php declare(strict_types=1);

use Lalaz\Security\Middleware\AuthorizationMiddleware;
use Lalaz\Security\Authorizable;
use Lalaz\Http\Request;
use Lalaz\Http\Response;

describe('AuthorizationMiddleware', function () {
    beforeEach(function () {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    });

    describe('constructor', function () {
        it('can be instantiated without required roles', function () {
            $middleware = new AuthorizationMiddleware();

            expect($middleware)->toBeInstanceOf(AuthorizationMiddleware::class);
        });

        it('can be instantiated with required roles', function () {
            $middleware = new AuthorizationMiddleware(['admin', 'editor']);

            expect($middleware)->toBeInstanceOf(AuthorizationMiddleware::class);
        });

        it('accepts empty array of roles', function () {
            $middleware = new AuthorizationMiddleware([]);

            expect($middleware)->toBeInstanceOf(AuthorizationMiddleware::class);
        });

        it('accepts single role', function () {
            $middleware = new AuthorizationMiddleware(['admin']);

            expect($middleware)->toBeInstanceOf(AuthorizationMiddleware::class);
        });
    });

    describe('handle() - with authorized user', function () {
        it('allows access when user has required role', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['admin', 'editor'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            // Should not die
            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('allows access when user has any of the required roles', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['editor', 'writer'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin', 'editor', 'moderator']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('allows access when no roles required', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['user'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware([]);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });
    });

    describe('handle() - with unauthorized user', function () {
        it('dies when user does not have required role', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['user'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('dies when user has no roles', function () {
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

            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('dies when user object is null', function () {
            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = null;
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('dies when user does not implement Authorizable', function () {
            $user = (object)['id' => 1, 'name' => 'Test'];

            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');
    });

    describe('role checking', function () {
        it('is case sensitive for role names', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['Admin']; // capital A
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin']); // lowercase a
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            // Should die because 'Admin' !== 'admin'
            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('checks using hasAnyRole method', function () {
            $user = new class {
                use Authorizable;
                public bool $hasAnyRoleCalled = false;
                public array $checkedRoles = [];

                protected function fetchRoles(): array
                {
                    return ['editor'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }

                public function hasAnyRole(array $roles): bool
                {
                    $this->hasAnyRoleCalled = true;
                    $this->checkedRoles = $roles;
                    return !empty(array_intersect($roles, $this->getRoles()));
                }
            };

            $requiredRoles = ['admin', 'editor'];
            $middleware = new AuthorizationMiddleware($requiredRoles);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            $middleware->handle($request, $response);

            expect($user->hasAnyRoleCalled)->toBeTrue();
            expect($user->checkedRoles)->toBe($requiredRoles);
        });
    });

    describe('edge cases', function () {
        it('handles user without user property on request', function () {
            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            // No user property set
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('handles false as user', function () {
            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = false;
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('handles empty string as user', function () {
            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = '';
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('handles zero as user', function () {
            $middleware = new AuthorizationMiddleware(['admin']);
            $request = new Request();
            $request->user = 0;
            $response = new Response();

            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');
    });

    describe('multiple roles', function () {
        it('accepts user with first role', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['admin'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin', 'editor', 'moderator']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('accepts user with middle role', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['editor'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin', 'editor', 'moderator']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('accepts user with last role', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['moderator'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin', 'editor', 'moderator']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });

        it('accepts user with multiple matching roles', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['admin', 'editor'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            $middleware = new AuthorizationMiddleware(['admin', 'moderator']);
            $request = new Request();
            $request->user = $user;
            $response = new Response();

            $middleware->handle($request, $response);

            expect(true)->toBeTrue();
        });
    });
});
