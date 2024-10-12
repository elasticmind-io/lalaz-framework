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
     *
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
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
}
