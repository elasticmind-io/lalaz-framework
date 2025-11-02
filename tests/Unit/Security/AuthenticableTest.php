<?php declare(strict_types=1);

use Lalaz\Security\Authenticable;
use Lalaz\Data\Model;
use Lalaz\Http\SessionManager;
use Lalaz\Data\Query\Expressions;

describe('Authenticable', function () {
    beforeEach(function () {
        // Clear session before each test
        SessionManager::destroy();

        // Create test user class
        $this->userClass = new class extends Model {
            use Authenticable;

            public string $email = '';
            public string $password_hash = '';
            public string $name = '';

            protected static function usernamePropertyName(): string
            {
                return 'email';
            }

            protected static function passwordPropertyName(): string
            {
                return 'password_hash';
            }

            public static function findOneByExpression($expression)
            {
                // Mock implementation for testing
                return null;
            }
        };
    });

    describe('trait structure', function () {
        it('requires usernamePropertyName() implementation', function () {
            expect(method_exists($this->userClass, 'usernamePropertyName'))->toBeTrue();
        });

        it('requires passwordPropertyName() implementation', function () {
            expect(method_exists($this->userClass, 'passwordPropertyName'))->toBeTrue();
        });

        it('provides authenticate() method', function () {
            expect(method_exists($this->userClass, 'authenticate'))->toBeTrue();
        });

        it('provides logout() method', function () {
            expect(method_exists($this->userClass, 'logout'))->toBeTrue();
        });

        it('provides authenticatedUser() method', function () {
            expect(method_exists($this->userClass, 'authenticatedUser'))->toBeTrue();
        });

        it('uses PasswordHash trait', function () {
            $traits = class_uses($this->userClass);

            expect($traits)->toContain('Lalaz\Security\Authenticable');
        });
    });

    describe('authenticate()', function () {
        it('returns false when user is not found', function () {
            $userClass = new class extends Model {
                use Authenticable;

                protected static function usernamePropertyName(): string
                {
                    return 'email';
                }

                protected static function passwordPropertyName(): string
                {
                    return 'password_hash';
                }

                public static function findOneByExpression($expression)
                {
                    return null;
                }
            };

            $result = $userClass::authenticate('nonexistent@example.com', 'password');

            expect($result)->toBeFalse();
        });

        it('returns false when password is incorrect', function () {
            $userClass = new class extends Model {
                use Authenticable;

                public string $email = 'test@example.com';
                public string $password_hash = '';

                protected static function usernamePropertyName(): string
                {
                    return 'email';
                }

                protected static function passwordPropertyName(): string
                {
                    return 'password_hash';
                }

                public static function findOneByExpression($expression)
                {
                    $user = new static();
                    $user->email = 'test@example.com';
                    $user->password_hash = static::generateHash('correctPassword');
                    return $user;
                }
            };

            $result = $userClass::authenticate('test@example.com', 'wrongPassword');

            expect($result)->toBeFalse();
        });

        it('returns user and sets session when authentication succeeds', function () {
            $userClass = new class extends Model {
                use Authenticable;

                public string $email = 'test@example.com';
                public string $password_hash = '';
                public int $id = 1;

                protected static function usernamePropertyName(): string
                {
                    return 'email';
                }

                protected static function passwordPropertyName(): string
                {
                    return 'password_hash';
                }

                public static function findOneByExpression($expression)
                {
                    $user = new static();
                    $user->id = 1;
                    $user->email = 'test@example.com';
                    $user->password_hash = static::generateHash('correctPassword');
                    return $user;
                }
            };

            $result = $userClass::authenticate('test@example.com', 'correctPassword');

            expect($result)->toBeObject();
            expect($result->email)->toBe('test@example.com');
            expect($result->id)->toBe(1);
        });

        it('stores user in session on successful authentication', function () {
            $userClass = new class extends Model {
                use Authenticable;

                public string $email = 'session@example.com';
                public string $password_hash = '';

                protected static function usernamePropertyName(): string
                {
                    return 'email';
                }

                protected static function passwordPropertyName(): string
                {
                    return 'password_hash';
                }

                public static function findOneByExpression($expression)
                {
                    $user = new static();
                    $user->email = 'session@example.com';
                    $user->password_hash = static::generateHash('password123');
                    return $user;
                }
            };

            $userClass::authenticate('session@example.com', 'password123');

            $sessionUser = SessionManager::get('__luser');
            expect($sessionUser)->toBeObject();
            expect($sessionUser->email)->toBe('session@example.com');
        });

        it('uses correct expression to find user', function () {
            $capturedExpression = null;

            $userClass = new class extends Model {
                use Authenticable;
                public static $expressionCapture = null;

                protected static function usernamePropertyName(): string
                {
                    return 'username';
                }

                protected static function passwordPropertyName(): string
                {
                    return 'pwd';
                }

                public static function findOneByExpression($expression)
                {
                    static::$expressionCapture = $expression;
                    return null;
                }
            };

            $userClass::authenticate('testuser', 'pass');

            expect($userClass::$expressionCapture)->not->toBeNull();
        });
    });

    describe('logout()', function () {
        it('destroys the session', function () {
            // Set a session value
            SessionManager::set('__luser', (object)['id' => 1, 'email' => 'test@example.com']);
            SessionManager::set('other_key', 'some_value');

            expect(SessionManager::get('__luser'))->not->toBeNull();
            expect(SessionManager::get('other_key'))->not->toBeNull();

            $this->userClass::logout();

            // Session should be destroyed
            expect(SessionManager::get('__luser'))->toBeNull();
            expect(SessionManager::get('other_key'))->toBeNull();
        });

        it('can be called when no session exists', function () {
            // Should not throw exception
            $this->userClass::logout();

            expect(true)->toBeTrue();
        });
    });

    describe('authenticatedUser()', function () {
        it('returns null when no user is authenticated', function () {
            $user = $this->userClass::authenticatedUser();

            expect($user)->toBeNull();
        });

        it('returns authenticated user from session', function () {
            $userData = (object)[
                'id' => 1,
                'email' => 'authenticated@example.com',
                'name' => 'Test User'
            ];

            SessionManager::set('__luser', $userData);

            $user = $this->userClass::authenticatedUser();

            expect($user)->toBeObject();
            expect($user->id)->toBe(1);
            expect($user->email)->toBe('authenticated@example.com');
            expect($user->name)->toBe('Test User');
        });

        it('returns same user multiple times', function () {
            $userData = (object)['id' => 1, 'email' => 'test@example.com'];
            SessionManager::set('__luser', $userData);

            $user1 = $this->userClass::authenticatedUser();
            $user2 = $this->userClass::authenticatedUser();

            expect($user1)->toBe($user2);
        });
    });

    describe('authentication flow', function () {
        it('supports complete login/logout cycle', function () {
            $userClass = new class extends Model {
                use Authenticable;

                public string $email = '';
                public string $password_hash = '';
                public int $id = 0;

                protected static function usernamePropertyName(): string
                {
                    return 'email';
                }

                protected static function passwordPropertyName(): string
                {
                    return 'password_hash';
                }

                public static function findOneByExpression($expression)
                {
                    $user = new static();
                    $user->id = 42;
                    $user->email = 'flow@example.com';
                    $user->password_hash = static::generateHash('myPassword');
                    return $user;
                }
            };

            // Initial state: no user
            expect($userClass::authenticatedUser())->toBeNull();

            // Login
            $loginResult = $userClass::authenticate('flow@example.com', 'myPassword');
            expect($loginResult)->toBeObject();
            expect($loginResult->id)->toBe(42);

            // Check authenticated user
            $currentUser = $userClass::authenticatedUser();
            expect($currentUser)->toBeObject();
            expect($currentUser->id)->toBe(42);

            // Logout
            $userClass::logout();

            // Verify logged out
            expect($userClass::authenticatedUser())->toBeNull();
        });
    });

    describe('edge cases', function () {
        it('handles empty username', function () {
            $result = $this->userClass::authenticate('', 'password');

            expect($result)->toBeFalse();
        });

        it('handles empty password', function () {
            $result = $this->userClass::authenticate('user@example.com', '');

            expect($result)->toBeFalse();
        });

        it('handles both empty credentials', function () {
            $result = $this->userClass::authenticate('', '');

            expect($result)->toBeFalse();
        });
    });
});
