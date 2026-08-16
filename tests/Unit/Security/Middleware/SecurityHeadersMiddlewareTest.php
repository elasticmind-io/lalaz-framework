<?php

declare(strict_types=1);

use Lalaz\Security\Middleware\SecurityHeadersMiddleware;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Core\Config;

beforeEach(function () {
    // Clear any existing headers
    if (function_exists('xdebug_get_headers')) {
        header_remove();
    }

    // Clear config
    Config::clearCache();
});

afterEach(function () {
    Config::clearCache();
});

test('middleware can be instantiated', function () {
    $middleware = new SecurityHeadersMiddleware();
    expect($middleware)->toBeInstanceOf(SecurityHeadersMiddleware::class);
});

test('minimal preset includes only basic headers', function () {
    $middleware = SecurityHeadersMiddleware::minimal();
    $headers = $middleware->getHeaders();

    expect($headers)->toHaveKey('X-Content-Type-Options')
        ->and($headers['X-Content-Type-Options'])->toBe('nosniff')
        ->and($headers)->toHaveKey('X-Frame-Options')
        ->and($headers['X-Frame-Options'])->toBe('SAMEORIGIN')
        ->and($headers['X-XSS-Protection'])->toBe(false)
        ->and($headers['Referrer-Policy'])->toBe(false)
        ->and($headers['Permissions-Policy'])->toBe(false);
});

test('recommended preset includes standard security headers', function () {
    $middleware = SecurityHeadersMiddleware::recommended();
    $headers = $middleware->getHeaders();

    expect($headers['X-Frame-Options'])->toBe('DENY')
        ->and($headers['X-Content-Type-Options'])->toBe('nosniff')
        ->and($headers['X-XSS-Protection'])->toBe('1; mode=block')
        ->and($headers['Referrer-Policy'])->toBe('strict-origin-when-cross-origin')
        ->and($headers['Permissions-Policy'])->toBe('geolocation=(), microphone=(), camera=()');
});

test('strict preset includes maximum security headers', function () {
    $middleware = SecurityHeadersMiddleware::strict();
    $headers = $middleware->getHeaders();

    expect($headers['X-Frame-Options'])->toBe('DENY')
        ->and($headers['X-Content-Type-Options'])->toBe('nosniff')
        ->and($headers['X-XSS-Protection'])->toBe('1; mode=block')
        ->and($headers['Referrer-Policy'])->toBe('no-referrer')
        ->and($headers['Permissions-Policy'])->toContain('geolocation=()')
        ->and($headers['Strict-Transport-Security'])->toContain('max-age=')
        ->and($headers)->toHaveKey('Content-Security-Policy');
});

test('api preset includes headers optimized for APIs', function () {
    $middleware = SecurityHeadersMiddleware::api();
    $headers = $middleware->getHeaders();

    expect($headers['X-Content-Type-Options'])->toBe('nosniff')
        ->and($headers['X-Frame-Options'])->toBe('DENY')
        ->and($headers['Referrer-Policy'])->toBe('no-referrer')
        ->and($headers['X-XSS-Protection'])->toBe(false)
        ->and($headers['Permissions-Policy'])->toBe(false);
});

test('custom headers override default headers', function () {
    $middleware = SecurityHeadersMiddleware::with([
        'X-Frame-Options' => 'ALLOW-FROM https://trusted.com',
        'X-Custom-Header' => 'custom-value',
    ]);

    $headers = $middleware->getHeaders();

    expect($headers['X-Frame-Options'])->toBe('ALLOW-FROM https://trusted.com')
        ->and($headers['X-Custom-Header'])->toBe('custom-value');
});

test('headers can be disabled by setting to false', function () {
    $middleware = SecurityHeadersMiddleware::with([
        'X-Frame-Options' => false,
        'X-XSS-Protection' => false,
    ]);

    $headers = $middleware->getHeaders();

    expect($headers['X-Frame-Options'])->toBe(false)
        ->and($headers['X-XSS-Protection'])->toBe(false);
});

test('headers can be disabled by setting to null', function () {
    $middleware = SecurityHeadersMiddleware::with([
        'X-Frame-Options' => null,
    ]);

    $headers = $middleware->getHeaders();

    expect($headers['X-Frame-Options'])->toBeNull();
});

test('reads headers from config', function () {
    Config::set('SECURITY_FRAME_OPTIONS', 'DENY');
    Config::set('SECURITY_CONTENT_TYPE_OPTIONS', 'nosniff');
    Config::set('SECURITY_XSS_PROTECTION', '1; mode=block');

    $middleware = new SecurityHeadersMiddleware();
    $headers = $middleware->getHeaders();

    expect($headers['X-Frame-Options'])->toBe('DENY')
        ->and($headers['X-Content-Type-Options'])->toBe('nosniff')
        ->and($headers['X-XSS-Protection'])->toBe('1; mode=block');
});

test('HSTS is only included when explicitly enabled', function () {
    Config::set('SECURITY_HSTS_ENABLED', false);

    $middleware = new SecurityHeadersMiddleware();
    $headers = $middleware->getHeaders();

    expect($headers)->not->toHaveKey('Strict-Transport-Security');
});

test('HSTS is included when enabled via config', function () {
    Config::set('SECURITY_HSTS_ENABLED', true);
    Config::set('SECURITY_HSTS_VALUE', 'max-age=31536000');

    $middleware = new SecurityHeadersMiddleware();
    $headers = $middleware->getHeaders();

    expect($headers)->toHaveKey('Strict-Transport-Security')
        ->and($headers['Strict-Transport-Security'])->toBe('max-age=31536000');
});

test('CSP is only included when configured', function () {
    $middleware = new SecurityHeadersMiddleware();
    $headers = $middleware->getHeaders();

    expect($headers)->not->toHaveKey('Content-Security-Policy');
});

test('CSP is included when configured', function () {
    Config::set('SECURITY_CSP', "default-src 'self'; script-src 'self' 'unsafe-inline'");

    $middleware = new SecurityHeadersMiddleware();
    $headers = $middleware->getHeaders();

    expect($headers)->toHaveKey('Content-Security-Policy')
        ->and($headers['Content-Security-Policy'])->toContain("default-src 'self'");
});

test('handle method can be called without errors', function () {
    // Mock the $_SERVER superglobal needed by Request
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test';

    $middleware = SecurityHeadersMiddleware::minimal();
    $request = new Request();
    $response = new Response();

    expect(fn() => $middleware->handle($request, $response))->not->toThrow(Exception::class);

    // Cleanup
    unset($_SERVER['REQUEST_METHOD']);
    unset($_SERVER['REQUEST_URI']);
});test('multiple presets can be chained', function () {
    // Start with minimal, then add custom headers
    $middleware1 = SecurityHeadersMiddleware::minimal();
    $headers1 = $middleware1->getHeaders();

    // Create new instance with additional headers
    $middleware2 = SecurityHeadersMiddleware::with(array_merge(
        $headers1,
        ['X-Custom-Header' => 'value']
    ));

    $headers2 = $middleware2->getHeaders();

    expect($headers2)->toHaveKey('X-Custom-Header')
        ->and($headers2['X-Custom-Header'])->toBe('value')
        ->and($headers2['X-Content-Type-Options'])->toBe('nosniff');
});

test('getHeaders returns all configured headers', function () {
    $customHeaders = [
        'X-Frame-Options' => 'DENY',
        'X-Custom-1' => 'value1',
        'X-Custom-2' => 'value2',
    ];

    $middleware = SecurityHeadersMiddleware::with($customHeaders);
    $headers = $middleware->getHeaders();

    expect($headers)->toBeArray()
        ->and(count($headers))->toBeGreaterThan(count($customHeaders))
        ->and($headers['X-Custom-1'])->toBe('value1')
        ->and($headers['X-Custom-2'])->toBe('value2');
});

test('empty custom headers array uses defaults', function () {
    $middleware = SecurityHeadersMiddleware::with([]);
    $headers = $middleware->getHeaders();

    expect($headers)->toBeArray()
        ->and(count($headers))->toBeGreaterThan(0)
        ->and($headers)->toHaveKey('X-Frame-Options')
        ->and($headers)->toHaveKey('X-Content-Type-Options');
});
