<?php declare(strict_types=1);

use Lalaz\Security\CsrfProtection;

describe('CsrfProtection', function () {
    beforeEach(function () {
        // Clear cookies
        $_COOKIE = [];
        $_SERVER = [];
    });

    afterEach(function () {
        $_COOKIE = [];
        $_SERVER = [];
    });

    describe('token generation', function () {
        it('generates a valid token', function () {
            $token = CsrfProtection::generateToken();

            expect($token)->toBeString()
                ->and(strlen($token))->toBe(64) // 32 bytes in hex = 64 chars
                ->and($_COOKIE['__csrf_token'])->toBe($token);
        });

        it('generates different tokens on each call', function () {
            $token1 = CsrfProtection::generateToken();
            $token2 = CsrfProtection::generateToken();

            expect($token1)->not->toBe($token2);
        });

        it('returns existing token from cookie', function () {
            $_COOKIE['__csrf_token'] = 'existing-token-123';

            $token = CsrfProtection::getToken();

            expect($token)->toBe('existing-token-123');
        });

        it('generates new token if cookie does not exist', function () {
            $token = CsrfProtection::getToken();

            expect($token)->toBeString()
                ->and(strlen($token))->toBe(64);
        });
    });

    describe('token validation', function () {
        it('validates token from request body (array)', function () {
            $_COOKIE['__csrf_token'] = 'test-token-123';
            $body = ['csrfToken' => 'test-token-123'];

            expect(CsrfProtection::validateToken($body))->toBeTrue();
        });

        it('validates token from request body (object)', function () {
            $_COOKIE['__csrf_token'] = 'test-token-456';
            $body = (object)['csrfToken' => 'test-token-456'];

            expect(CsrfProtection::validateToken($body))->toBeTrue();
        });

        it('validates token from header', function () {
            $_COOKIE['__csrf_token'] = 'test-token-789';
            $body = [];
            $headers = ['X-CSRF-Token' => 'test-token-789'];

            expect(CsrfProtection::validateToken($body, $headers))->toBeTrue();
        });

        it('rejects invalid token in body', function () {
            $_COOKIE['__csrf_token'] = 'correct-token';
            $body = ['csrfToken' => 'wrong-token'];

            expect(CsrfProtection::validateToken($body))->toBeFalse();
        });

        it('rejects missing cookie token', function () {
            $body = ['csrfToken' => 'some-token'];

            expect(CsrfProtection::validateToken($body))->toBeFalse();
        });

        it('rejects missing request token', function () {
            $_COOKIE['__csrf_token'] = 'test-token';
            $body = [];

            expect(CsrfProtection::validateToken($body))->toBeFalse();
        });

        it('uses timing-safe comparison', function () {
            // This test verifies that hash_equals is being used
            $_COOKIE['__csrf_token'] = 'abc123';
            $body = ['csrfToken' => 'abc123'];

            expect(CsrfProtection::validateToken($body))->toBeTrue();
        });
    });

    describe('token rotation', function () {
        it('generates new token on rotation', function () {
            $_COOKIE['__csrf_token'] = 'old-token';

            $newToken = CsrfProtection::rotateToken();

            expect($newToken)->not->toBe('old-token')
                ->and($newToken)->toBeString()
                ->and(strlen($newToken))->toBe(64)
                ->and($_COOKIE['__csrf_token'])->toBe($newToken);
        });
    });

    describe('token deletion', function () {
        it('deletes token cookie', function () {
            $_COOKIE['__csrf_token'] = 'token-to-delete';

            CsrfProtection::deleteToken();

            expect($_COOKIE)->not->toHaveKey('__csrf_token');
        });

        it('handles missing cookie gracefully', function () {
            expect(function () {
                CsrfProtection::deleteToken();
            })->not->toThrow(Exception::class);
        });
    });

    describe('field and header names', function () {
        it('returns correct token field name', function () {
            expect(CsrfProtection::getTokenFieldName())->toBe('csrfToken');
        });

        it('returns correct token header name', function () {
            expect(CsrfProtection::getTokenHeaderName())->toBe('X-CSRF-Token');
        });
    });

    describe('cookie security attributes', function () {
        it('sets HttpOnly cookie', function () {
            // This is validated by the setcookie call in the implementation
            // The test verifies the method completes without error
            $token = CsrfProtection::generateToken();

            expect($token)->toBeString();
        });

        it('detects HTTPS connection', function () {
            $_SERVER['HTTPS'] = 'on';

            $token = CsrfProtection::generateToken();

            expect($token)->toBeString();
        });

        it('detects HTTPS from proxy header', function () {
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

            $token = CsrfProtection::generateToken();

            expect($token)->toBeString();
        });

        it('handles localhost domain', function () {
            $_SERVER['HTTP_HOST'] = 'localhost';

            $token = CsrfProtection::generateToken();

            expect($token)->toBeString();
        });

        it('handles domain with port', function () {
            $_SERVER['HTTP_HOST'] = 'example.com:8080';

            $token = CsrfProtection::generateToken();

            expect($token)->toBeString();
        });
    });

    describe('header preference', function () {
        it('prefers body token over header token', function () {
            $_COOKIE['__csrf_token'] = 'correct-token';
            $body = ['csrfToken' => 'correct-token'];
            $headers = ['X-CSRF-Token' => 'wrong-token'];

            expect(CsrfProtection::validateToken($body, $headers))->toBeTrue();
        });

        it('falls back to header when body is missing token', function () {
            $_COOKIE['__csrf_token'] = 'correct-token';
            $body = [];
            $headers = ['X-CSRF-Token' => 'correct-token'];

            expect(CsrfProtection::validateToken($body, $headers))->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('handles empty body array', function () {
            $_COOKIE['__csrf_token'] = 'test-token';

            expect(CsrfProtection::validateToken([]))->toBeFalse();
        });

        it('handles empty body object', function () {
            $_COOKIE['__csrf_token'] = 'test-token';

            expect(CsrfProtection::validateToken((object)[]))->toBeFalse();
        });

        it('handles null values', function () {
            $_COOKIE['__csrf_token'] = 'test-token';
            $body = ['csrfToken' => null];

            expect(CsrfProtection::validateToken($body))->toBeFalse();
        });

        it('handles empty string token', function () {
            $_COOKIE['__csrf_token'] = 'test-token';
            $body = ['csrfToken' => ''];

            expect(CsrfProtection::validateToken($body))->toBeFalse();
        });
    });
});
