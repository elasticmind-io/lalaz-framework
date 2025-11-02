<?php declare(strict_types=1);

use Lalaz\Security\Middleware\AuthenticationMiddleware;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Http\SessionManager;

describe('AuthenticationMiddleware', function () {
    beforeEach(function () {
        SessionManager::destroy();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    });

    describe('constructor', function () {
        it('can be instantiated without login URL', function () {
            $middleware = new AuthenticationMiddleware();

            expect($middleware)->toBeInstanceOf(AuthenticationMiddleware::class);
        });

        it('can be instantiated with login URL', function () {
            $middleware = new AuthenticationMiddleware('/login');

            expect($middleware)->toBeInstanceOf(AuthenticationMiddleware::class);
        });

        it('accepts empty string as login URL', function () {
            $middleware = new AuthenticationMiddleware('');

            expect($middleware)->toBeInstanceOf(AuthenticationMiddleware::class);
        });
    });

    describe('handle() - with authenticated user', function () {
        it('sets user on request when authenticated', function () {
            $user = (object)['id' => 1, 'email' => 'test@example.com'];
            SessionManager::set('__luser', $user);

            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect($request->user)->toBeObject();
            expect($request->user->id)->toBe(1);
            expect($request->user->email)->toBe('test@example.com');
        });

        it('sets exact user object from session', function () {
            $userData = (object)[
                'id' => 42,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'admin'
            ];
            SessionManager::set('__luser', $userData);

            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect($request->user)->toBe($userData);
            expect($request->user->name)->toBe('John Doe');
            expect($request->user->role)->toBe('admin');
        });

        it('does not redirect when user is authenticated', function () {
            SessionManager::set('__luser', (object)['id' => 1]);

            $middleware = new AuthenticationMiddleware('/login');
            $request = new Request();
            $response = new Response();

            // Capture if redirect was called
            $redirectCalled = false;
            $originalRedirect = null;

            $middleware->handle($request, $response);

            // If we reach here without dying, no redirect occurred
            expect($request->user)->toBeObject();
        });
    });

    describe('handle() - without authenticated user', function () {
        it('dies with "Forbidden" when no login URL provided', function () {
            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            // This will call die('Forbidden')
            // We expect this to throw or terminate
            expect(function () use ($middleware, $request, $response) {
                $middleware->handle($request, $response);
            })->toThrow(Exception::class);
        })->skip('Cannot test die() without process isolation');

        it('redirects to login URL when provided and user not authenticated', function () {
            $middleware = new AuthenticationMiddleware('/auth/login');
            $request = new Request();
            $response = new Response();

            // Mock response to track redirect
            $response = new class extends Response {
                public bool $redirectCalled = false;
                public string $redirectUrl = '';

                public function redirect(string $url): void
                {
                    $this->redirectCalled = true;
                    $this->redirectUrl = $url;
                    // Don't actually redirect
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectCalled)->toBeTrue();
            expect($response->redirectUrl)->toBe('/auth/login');
        });

        it('redirects to root when login URL is "/"', function () {
            $middleware = new AuthenticationMiddleware('/');
            $request = new Request();

            $response = new class extends Response {
                public string $redirectUrl = '';

                public function redirect(string $url): void
                {
                    $this->redirectUrl = $url;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectUrl)->toBe('/');
        });

        it('uses exact login URL provided in constructor', function () {
            $loginUrl = '/custom/authentication/page';
            $middleware = new AuthenticationMiddleware($loginUrl);
            $request = new Request();

            $response = new class extends Response {
                public string $redirectUrl = '';

                public function redirect(string $url): void
                {
                    $this->redirectUrl = $url;
                }
            };

            $middleware->handle($request, $response);

            expect($response->redirectUrl)->toBe($loginUrl);
        });
    });

    describe('session key usage', function () {
        it('uses "__luser" as session key', function () {
            $user = (object)['id' => 1];
            SessionManager::set('__luser', $user);

            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect($request->user)->toBe($user);
        });

        it('does not authenticate with different session key', function () {
            SessionManager::set('user', (object)['id' => 1]);
            SessionManager::set('current_user', (object)['id' => 2]);

            $middleware = new AuthenticationMiddleware('/login');
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
        it('handles null user in session', function () {
            SessionManager::set('__luser', null);

            $middleware = new AuthenticationMiddleware('/login');
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

        it('handles false user in session', function () {
            SessionManager::set('__luser', false);

            $middleware = new AuthenticationMiddleware('/login');
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

        it('handles empty string as user in session', function () {
            SessionManager::set('__luser', '');

            $middleware = new AuthenticationMiddleware('/login');
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

        it('handles zero as user in session', function () {
            SessionManager::set('__luser', 0);

            $middleware = new AuthenticationMiddleware('/login');
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

        it('accepts truthy user values', function () {
            SessionManager::set('__luser', 1); // truthy value

            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect($request->user)->toBe(1);
        });

        it('accepts array as user', function () {
            $userArray = ['id' => 1, 'email' => 'test@example.com'];
            SessionManager::set('__luser', $userArray);

            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            $middleware->handle($request, $response);

            expect($request->user)->toBe($userArray);
        });
    });

    describe('request mutation', function () {
        it('adds user property to request object', function () {
            $user = (object)['id' => 99];
            SessionManager::set('__luser', $user);

            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            expect(property_exists($request, 'user'))->toBeFalse();

            $middleware->handle($request, $response);

            expect(property_exists($request, 'user'))->toBeTrue();
        });

        it('does not modify response when user is authenticated', function () {
            SessionManager::set('__luser', (object)['id' => 1]);

            $middleware = new AuthenticationMiddleware();
            $request = new Request();
            $response = new Response();

            $originalResponse = clone $response;

            $middleware->handle($request, $response);

            // Response should remain unchanged (no redirect, no body modification)
            expect($response)->toEqual($originalResponse);
        });
    });
});
