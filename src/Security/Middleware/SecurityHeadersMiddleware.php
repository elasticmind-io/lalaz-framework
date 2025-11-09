<?php declare(strict_types=1);

namespace Lalaz\Security\Middleware;

use Lalaz\Http\Middleware;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Core\Config;

/**
 * SecurityHeadersMiddleware
 *
 * Adds configurable HTTP security headers to responses.
 * The developer chooses which headers to use and where to apply them.
 *
 * Usage Examples:
 *
 * 1. On specific routes:
 *    $router->get('/api/data', 'ApiController@getData')
 *        ->middleware(SecurityHeadersMiddleware::recommended());
 *
 * 2. On route groups:
 *    $router->group('/admin', function($router) {
 *        // routes...
 *    })->middleware(SecurityHeadersMiddleware::strict());
 *
 * 3. With custom headers:
 *    $router->get('/embed', 'EmbedController@show')
 *        ->middleware(SecurityHeadersMiddleware::with([
 *            'X-Frame-Options' => 'ALLOW-FROM https://trusted.com',
 *        ]));
 *
 * 4. Globally (optional):
 *    $router->middleware(SecurityHeadersMiddleware::recommended());
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class SecurityHeadersMiddleware extends Middleware
{
    /**
     * @var array<string, string|false|null> Headers to be added to the response
     */
    private array $headers = [];

    /**
     * Constructor
     *
     * @param array<string, string|false|null> $customHeaders Custom headers to apply
     */
    public function __construct(array $customHeaders = [])
    {
        // Default headers (can be overridden via config or constructor)
        $defaults = [
            'X-Frame-Options' => Config::get('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
            'X-Content-Type-Options' => Config::get('SECURITY_CONTENT_TYPE_OPTIONS', 'nosniff'),
            'X-XSS-Protection' => Config::get('SECURITY_XSS_PROTECTION', '1; mode=block'),
            'Referrer-Policy' => Config::get('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
            'Permissions-Policy' => Config::get('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
        ];

        // HSTS (only if explicitly enabled)
        if (Config::getTyped('SECURITY_HSTS_ENABLED', false, 'bool')) {
            $defaults['Strict-Transport-Security'] = Config::get(
                'SECURITY_HSTS_VALUE',
                'max-age=31536000; includeSubDomains'
            );
        }

        // CSP (only if explicitly configured)
        if ($csp = Config::get('SECURITY_CSP')) {
            $defaults['Content-Security-Policy'] = $csp;
        }

        // Merge with custom headers (custom headers take precedence)
        $this->headers = array_merge($defaults, $customHeaders);
    }

    /**
     * Handle the middleware logic
     *
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @return void
     */
    public function handle(Request $request, Response $response): void
    {
        // Add headers to the response
        foreach ($this->headers as $header => $value) {
            // Skip if value is false or null (allows disabling specific headers)
            if ($value !== null && $value !== false) {
                header("$header: $value", true);
            }
        }
    }

    /**
     * Create middleware instance with custom headers
     *
     * @param array<string, string|false|null> $headers Custom headers
     * @return self
     */
    public static function with(array $headers): self
    {
        return new self($headers);
    }

    /**
     * Preset: Minimal headers (most permissive)
     *
     * Includes only the most basic security headers:
     * - X-Content-Type-Options: nosniff
     * - X-Frame-Options: SAMEORIGIN
     *
     * Use when you need basic protection without breaking functionality.
     *
     * @return self
     */
    public static function minimal(): self
    {
        return new self([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => false,
            'Referrer-Policy' => false,
            'Permissions-Policy' => false,
        ]);
    }

    /**
     * Preset: Recommended headers for production
     *
     * Includes recommended security headers for most applications:
     * - X-Frame-Options: DENY
     * - X-Content-Type-Options: nosniff
     * - X-XSS-Protection: 1; mode=block
     * - Referrer-Policy: strict-origin-when-cross-origin
     * - Permissions-Policy: restricts geolocation, microphone, camera
     *
     * Good balance between security and functionality.
     *
     * @return self
     */
    public static function recommended(): self
    {
        return new self([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ]);
    }

    /**
     * Preset: Strict headers (most restrictive)
     *
     * Includes maximum security headers:
     * - X-Frame-Options: DENY
     * - X-Content-Type-Options: nosniff
     * - X-XSS-Protection: 1; mode=block
     * - Referrer-Policy: no-referrer
     * - Permissions-Policy: restricts most browser features
     * - Strict-Transport-Security: enforces HTTPS for 2 years
     * - Content-Security-Policy: restrictive CSP
     *
     * Use for high-security applications (admin panels, financial apps).
     * May require adjustments to work with your specific application.
     *
     * @return self
     */
    public static function strict(): self
    {
        return new self([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()',
            'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains; preload',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
        ]);
    }

    /**
     * Preset: API headers
     *
     * Optimized for REST APIs:
     * - X-Content-Type-Options: nosniff
     * - X-Frame-Options: DENY (APIs shouldn't be framed)
     * - Referrer-Policy: no-referrer (better privacy)
     *
     * @return self
     */
    public static function api(): self
    {
        return new self([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
            'X-XSS-Protection' => false, // Not needed for JSON APIs
            'Permissions-Policy' => false,
        ]);
    }

    /**
     * Get the configured headers
     *
     * @return array<string, string|false|null>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
