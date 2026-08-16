<?php declare(strict_types=1);

namespace Lalaz\Http;

/**
 * Class SessionManager
 *
 * Handles session operations for authentication, allowing storage and retrieval
 * of session data in a standardized way.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class SessionManager
{
    /**
     * Starts the session if not already started.
     * Applies security hardening configurations to prevent session hijacking and fixation attacks.
     *
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Security: HttpOnly prevents JavaScript access to session cookies (XSS protection)
            ini_set('session.cookie_httponly', '1');

            // Security: Secure flag ensures cookies only sent over HTTPS
            ini_set('session.cookie_secure', '1');

            // Security: SameSite=Strict prevents CSRF attacks
            ini_set('session.cookie_samesite', 'Strict');

            // Security: Strict mode rejects uninitialized session IDs (prevents session fixation)
            ini_set('session.use_strict_mode', '1');

            // Security: Only accept session IDs from cookies, not URL parameters
            ini_set('session.use_only_cookies', '1');

            // Configure session lifetime
            // com default: sem SESSION_LIFETIME definido, config() devolve null e
            // ini_set(null) e deprecado no PHP 8.1+. Antes este bloco nunca rodava,
            // entao o aviso ficava latente; agora ele roda em toda requisicao.
            ini_set('session.gc_maxlifetime', (string) ((int) config('SESSION_LIFETIME', 7200)));

            session_start();

            // Initialize session fingerprint on first access
            if (!isset($_SESSION['__fingerprint'])) {
                static::initializeFingerprint();
            }

            // Validate session fingerprint to prevent hijacking
            static::validateFingerprint();
        }
    }

    /**
     * Sets a session variable.
     *
     * @param string $key The session key.
     * @param mixed $value The session value.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Gets a session variable.
     *
     * @param string $key The session key.
     * @return mixed|null The session value or null if not found.
     */
    public static function get(string $key): mixed
    {
        self::start();
        return $_SESSION[$key] ?? null;
    }

    /**
     * Removes a session variable.
     *
     * @param string $key The session key to unset.
     * @return void
     */
    public static function unset(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Destroys the current session.
     *
     * @return void
     */
    public static function destroy(): void
    {
        self::start();
        session_unset();
        session_destroy();
    }

    /**
     * Regenerates the session ID to prevent session fixation attacks.
     * Should be called after successful login or privilege escalation.
     *
     * @param bool $deleteOldSession Whether to delete the old session file (default: true)
     * @return void
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::start();
        session_regenerate_id($deleteOldSession);

        // Update fingerprint after regeneration
        static::initializeFingerprint();
    }

    /**
     * Initializes session fingerprint with user agent and partial IP.
     * Used to detect session hijacking attempts.
     *
     * @return void
     */
    private static function initializeFingerprint(): void
    {
        $_SESSION['__fingerprint'] = hash('sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') .
            static::getPartialIp()
        );
    }

    /**
     * Validates the session fingerprint against current request.
     * Destroys session if fingerprint doesn't match (potential hijacking).
     *
     * @return void
     */
    private static function validateFingerprint(): void
    {
        if (!isset($_SESSION['__fingerprint'])) {
            return;
        }

        $currentFingerprint = hash('sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') .
            static::getPartialIp()
        );

        // If fingerprint doesn't match, destroy session (potential hijacking)
        if (!hash_equals($_SESSION['__fingerprint'], $currentFingerprint)) {
            session_unset();
            session_destroy();
            session_start();
        }
    }

    /**
     * Gets partial IP address (first 3 octets for IPv4, first 4 groups for IPv6).
     * This allows for some IP flexibility (e.g., mobile networks) while maintaining security.
     *
     * @return string Partial IP address
     */
    private static function getPartialIp(): string
    {
        // Request::clientIp() e nao REMOTE_ADDR: atras de proxy (o codeinit.dev
        // fica atras do Cloudflare) o REMOTE_ADDR e o IP da borda, que varia
        // entre requisicoes. Como divergencia de fingerprint destroi a sessao,
        // ler o IP errado significava logout aleatorio no meio do trabalho.
        $ip = Request::clientIp();

        // IPv4: Keep first 3 octets (e.g., 192.168.1.x)
        if (strpos($ip, '.') !== false) {
            $parts = explode('.', $ip);
            return implode('.', array_slice($parts, 0, 3));
        }

        // IPv6: Keep first 4 groups (e.g., 2001:0db8:85a3:0000:xxxx:xxxx:xxxx:xxxx)
        if (strpos($ip, ':') !== false) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4));
        }

        return $ip;
    }

    /**
     * Checks if the current session is valid and not expired.
     *
     * @return bool True if session is valid, false otherwise
     */
    public static function isValid(): bool
    {
        self::start();

        // Check if session has fingerprint (initialized)
        if (!isset($_SESSION['__fingerprint'])) {
            return false;
        }

        // Check if session has last activity timestamp
        if (!isset($_SESSION['__last_activity'])) {
            $_SESSION['__last_activity'] = time();
            return true;
        }

        // Check for session timeout
        $sessionLifetime = (int) config('SESSION_LIFETIME', 7200);
        if (time() - $_SESSION['__last_activity'] > $sessionLifetime) {
            static::destroy();
            return false;
        }

        // Update last activity timestamp
        $_SESSION['__last_activity'] = time();

        return true;
    }
}
