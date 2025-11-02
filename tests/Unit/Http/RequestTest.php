<?php declare(strict_types=1);

use Lalaz\Http\Request;
use Lalaz\Http\UploadedFile;
use Lalaz\Security\CsrfProtection;
use Lalaz\Exceptions\HttpException;

describe('Request', function() {

    beforeEach(function() {
        // Reset superglobals
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_SESSION = [];
        $_FILES = [];
    });

    describe('Constructor and Method', function() {
        it('initializes with default GET method', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $request = new Request();

            expect($request->method())->toBe('GET');
        });

        it('initializes with POST method', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';

            $request = new Request();

            expect($request->method())->toBe('POST');
        });

        it('converts method to uppercase', function() {
            $_SERVER['REQUEST_METHOD'] = 'post';

            $request = new Request();

            expect($request->method())->toBe('POST');
        });

        it('merges path params with GET params', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['page' => '1', 'sort' => 'name'];
            $pathParams = ['id' => '42', 'action' => 'view'];

            $request = new Request($pathParams);

            expect($request->params('id'))->toBe('42');
            expect($request->params('action'))->toBe('view');
            expect($request->params('page'))->toBe('1');
            expect($request->params('sort'))->toBe('name');
        });
    });

    describe('Params', function() {
        it('returns all params when no name provided', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['name' => 'John', 'age' => '25'];

            $request = new Request();
            $params = $request->params();

            expect($params)->toBeArray();
            expect($params)->toHaveKey('name');
            expect($params)->toHaveKey('age');
        });

        it('returns specific param by name', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['name' => 'John', 'age' => '25'];

            $request = new Request();

            expect($request->params('name'))->toBe('John');
            expect($request->params('age'))->toBe('25');
        });

        it('returns null for non-existent param', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['name' => 'John'];

            $request = new Request();

            expect($request->params('nonexistent'))->toBeNull();
        });

        it('sanitizes params', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['name' => '<script>alert("xss")</script>'];

            $request = new Request();

            $name = $request->params('name');
            expect($name)->not->toContain('<script>');
        });
    });

    describe('Current Page', function() {
        it('returns 1 as default page', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $request = new Request();

            expect($request->currentPage())->toBe(1);
        });

        it('returns page from default param name', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['p' => '5'];

            $request = new Request();

            expect($request->currentPage())->toBe(5);
        });

        it('returns page from custom param name', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['page' => '10'];

            $request = new Request();

            expect($request->currentPage('page'))->toBe(10);
        });

        it('converts string page to integer', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['p' => '42'];

            $request = new Request();

            expect($request->currentPage())->toBe(42);
            expect($request->currentPage())->toBeInt();
        });
    });

    describe('Body', function() {
        it('returns body data', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['name' => 'John', 'email' => 'john@example.com'];

            $request = new Request();
            $body = $request->body();

            expect($body)->toBeArray();
            expect($body)->toHaveKey('name');
            expect($body)->toHaveKey('email');
        });

        it('returns unsafe body without sanitization', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['name' => '<script>alert("xss")</script>'];

            $request = new Request();

            // unsafeBody should return raw POST data
            expect($request->unsafeBody('name'))->toBe('<script>alert("xss")</script>');
        });

        it('returns all unsafe body when no name provided', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['name' => 'John', 'age' => '25'];

            $request = new Request();
            $unsafeBody = $request->unsafeBody();

            expect($unsafeBody)->toBe($_POST);
        });

        it('returns empty string for non-existent unsafe body field', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['name' => 'John'];

            $request = new Request();

            expect($request->unsafeBody('nonexistent'))->toBe('');
        });
    });

    describe('Cookies', function() {
        it('returns cookie by name', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_COOKIE = ['session_id' => 'abc123', 'user_id' => '42'];

            $request = new Request();

            expect($request->cookie('session_id'))->toBe('abc123');
            expect($request->cookie('user_id'))->toBe('42');
        });
    });

    describe('Session', function() {
        it('returns session variable by name', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SESSION = ['user_id' => '42', 'username' => 'john'];

            $request = new Request();

            expect($request->session('user_id'))->toBe('42');
            expect($request->session('username'))->toBe('john');
        });
    });

    describe('File Upload', function() {
        it('returns null when file does not exist', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';

            $request = new Request();

            expect($request->file('avatar'))->toBeNull();
        });

        it('returns null when file has upload error', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_FILES = [
                'avatar' => [
                    'name' => 'photo.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => '/tmp/php123',
                    'error' => UPLOAD_ERR_INI_SIZE,
                    'size' => 1024
                ]
            ];

            $request = new Request();

            expect($request->file('avatar'))->toBeNull();
        });

        it('returns UploadedFile instance for valid upload', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_FILES = [
                'avatar' => [
                    'name' => 'photo.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => '/tmp/php123',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 1024
                ]
            ];

            $request = new Request();

            expect($request->file('avatar'))->toBeInstanceOf(UploadedFile::class);
        });
    });

    describe('IP Address', function() {
        it('returns IP from REMOTE_ADDR', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

            $request = new Request();

            expect($request->ip())->toBe('192.168.1.1');
        });

        it('prioritizes X-Forwarded-For header', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

            $request = new Request();

            expect($request->ip())->toBe('203.0.113.1');
        });

        it('prioritizes CF-Connecting-IP header', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.1';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

            $request = new Request();

            expect($request->ip())->toBe('198.51.100.1');
        });
    });

    describe('Edge Cases', function() {
        it('handles empty GET params', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = [];

            $request = new Request();

            expect($request->params())->toBe([]);
        });

        it('handles empty POST body', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [];

            $request = new Request();

            expect($request->body())->toBe([]);
        });

        it('handles numeric param values', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['id' => '123', 'score' => '99.5'];

            $request = new Request();

            expect($request->params('id'))->toBeString();
            expect($request->params('score'))->toBeString();
        });

        it('handles special characters in params', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['search' => 'hello world'];

            $request = new Request();

            expect($request->params('search'))->toContain('hello world');
        });
    });

    describe('CSRF Token Validation', function() {
        beforeEach(function () {
            $_COOKIE = [];
        });

        it('skips validation for GET requests', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $request = new Request();

            expect(function () use ($request) {
                $request->validateCsrfToken();
            })->not->toThrow(HttpException::class);
        });

        it('validates CSRF token for POST requests', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $token = CsrfProtection::generateToken();
            $_POST = ['csrfToken' => $token, 'name' => 'John'];

            $request = new Request();

            expect(function () use ($request) {
                $request->validateCsrfToken();
            })->not->toThrow(HttpException::class);
        });

        it('validates CSRF token for PUT requests', function() {
            $_SERVER['REQUEST_METHOD'] = 'PUT';
            $token = CsrfProtection::generateToken();
            $_POST = ['csrfToken' => $token];

            $request = new Request();

            expect(function () use ($request) {
                $request->validateCsrfToken();
            })->not->toThrow(HttpException::class);
        });

        it('validates CSRF token for PATCH requests', function() {
            $_SERVER['REQUEST_METHOD'] = 'PATCH';
            $token = CsrfProtection::generateToken();
            $_POST = ['csrfToken' => $token];

            $request = new Request();

            expect(function () use ($request) {
                $request->validateCsrfToken();
            })->not->toThrow(HttpException::class);
        });

        it('throws exception for invalid CSRF token', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            CsrfProtection::generateToken();
            $_POST = ['csrfToken' => 'invalid-token'];

            $request = new Request();

            expect(function () use ($request) {
                $request->validateCsrfToken();
            })->toThrow(HttpException::class);
        });

        it('throws exception for missing CSRF token', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            CsrfProtection::generateToken();
            $_POST = ['name' => 'John'];

            $request = new Request();

            expect(function () use ($request) {
                $request->validateCsrfToken();
            })->toThrow(HttpException::class);
        });

        it('rotates token after successful validation', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $originalToken = CsrfProtection::generateToken();
            $_POST = ['csrfToken' => $originalToken];

            $request = new Request();
            $request->validateCsrfToken();

            $newToken = $_COOKIE['__csrf_token'];
            expect($newToken)->not->toBe($originalToken);
        });

        it('validates token from header for AJAX requests', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $token = CsrfProtection::generateToken();
            $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
            $_POST = [];

            $request = new Request();

            expect(function () use ($request) {
                $request->validateCsrfToken();
            })->not->toThrow(HttpException::class);
        });

        it('includes forensic data in exception', function() {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
            $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
            CsrfProtection::generateToken();
            $_POST = ['csrfToken' => 'wrong-token'];

            $request = new Request();

            try {
                $request->validateCsrfToken();
                expect(false)->toBeTrue(); // Should not reach here
            } catch (HttpException $e) {
                $context = $e->getContext();
                expect($context)->toHaveKey('ip');
                expect($context)->toHaveKey('user_agent');
                expect($context['ip'])->toBe('192.168.1.100');
                expect($context['user_agent'])->toBe('Mozilla/5.0');
            }
        });
    });

    describe('CSRF Token Getter', function() {
        it('returns existing CSRF token', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_COOKIE['__csrf_token'] = 'test-token-123';

            $request = new Request();

            expect($request->csrfToken())->toBe('test-token-123');
        });

        it('generates new CSRF token if not exists', function() {
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $request = new Request();
            $token = $request->csrfToken();

            expect($token)->toBeString()
                ->and(strlen($token))->toBe(64);
        });
    });
});

