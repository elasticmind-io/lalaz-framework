<?php declare(strict_types=1);

namespace Lalaz\Security;

/**
 * Class CsrfProtection
 *
 * Provides CSRF token generation, validation, and rotation using HttpOnly cookies.
 * Implements SameSite=Strict for enhanced security against CSRF attacks.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class CsrfProtection
{
    /** @var string The name of the CSRF token cookie */
    private const COOKIE_NAME = '__csrf_token';

    /** @var string The name of the CSRF token in request body/headers */
    private const TOKEN_FIELD = 'csrfToken';

    /** @var string The header name for CSRF token */
    private const HEADER_NAME = 'X-CSRF-Token';

    /** @var int Cookie lifetime (1 day in seconds) */
    private const COOKIE_LIFETIME = 86400;

    /**
     * Generates a new CSRF token and sets it as an HttpOnly cookie.
     *
     * @return string The generated CSRF token
     */
    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        static::setCookie($token);
        return $token;
    }

    /**
     * Retrieves the current CSRF token from cookie or generates a new one.
     *
     * @return string The CSRF token
     */
    public static function getToken(): string
    {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            return $_COOKIE[self::COOKIE_NAME];
        }

        return static::generateToken();
    }

    /**
     * Validates the CSRF token from request against the cookie value.
     *
     * @param array|object $body The request body
     * @param array $headers The request headers
     * @return bool True if token is valid, false otherwise
     */
    public static function validateToken(array|object $body, array $headers = []): bool
    {
        $cookieToken = $_COOKIE[self::COOKIE_NAME] ?? null;

        if (!$cookieToken) {
            return false;
        }

        // Try to get token from body
        $requestToken = null;
        if (is_array($body) && isset($body[self::TOKEN_FIELD])) {
            $requestToken = $body[self::TOKEN_FIELD];
        } elseif (is_object($body) && isset($body->{self::TOKEN_FIELD})) {
            $requestToken = $body->{self::TOKEN_FIELD};
        }

        // Try to get token from header as fallback (for AJAX requests)
        // Case-insensitive header lookup
        if (!$requestToken) {
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, self::HEADER_NAME) === 0) {
                    $requestToken = $value;
                    break;
                }
            }
        }

        if (!$requestToken) {
            return false;
        }

        // Use hash_equals to prevent timing attacks
        return hash_equals($cookieToken, $requestToken);
    }

    /**
     * Rotates the CSRF token by generating a new one.
     * Should be called after successful state-changing operations (POST, PUT, PATCH, DELETE).
     *
     * @return string The new CSRF token
     */
    public static function rotateToken(): string
    {
        return static::generateToken();
    }

    /**
     * Deletes the CSRF token cookie.
     *
     * @return void
     */
    public static function deleteToken(): void
    {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            setcookie(
                self::COOKIE_NAME,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => static::getCookieDomain(),
                    'secure' => static::isSecure(),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );
            unset($_COOKIE[self::COOKIE_NAME]);
        }
    }

    /**
     * Sets the CSRF token as an HttpOnly cookie with SameSite=Strict.
     *
     * @param string $token The token to set
     * @return void
     */
    private static function setCookie(string $token): void
    {
        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires' => time() + self::COOKIE_LIFETIME,
                'path' => '/',
                'domain' => static::getCookieDomain(),
                'secure' => static::isSecure(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );

        // Set in $_COOKIE for immediate availability
        $_COOKIE[self::COOKIE_NAME] = $token;
    }

    /**
     * Determines if the connection is secure (HTTPS).
     *
     * @return bool True if HTTPS, false otherwise
     */
    private static function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Gets the appropriate cookie domain.
     *
     * @return string The cookie domain
     */
    private static function getCookieDomain(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';

        // Remove port if present
        $host = explode(':', $host)[0];

        // For localhost, return empty string
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return '';
        }

        return $host;
    }

    /**
     * Gets the token field name for use in forms.
     *
     * @return string The token field name
     */
    public static function getTokenFieldName(): string
    {
        return self::TOKEN_FIELD;
    }

    /**
     * Gets the token header name for use in AJAX requests.
     *
     * @return string The token header name
     */
    public static function getTokenHeaderName(): string
    {
        return self::HEADER_NAME;
    }
}
