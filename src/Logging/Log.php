<?php declare(strict_types=1);

namespace Lalaz\Logging;

use Lalaz\Lalaz;

/**
 * Class Log
 *
 * This class provides static methods for logging different levels of messages (info, debug, error).
 * It acts as a facade for accessing the current logger instance in the application and routing
 * log messages to the appropriate methods in the logger.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Logging
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
final class Log
{
    /**
     * Logs an informational message.
     *
     * @param mixed $message The message to log as info.
     *
     * @return void
     */
    public static function info($message): void
    {
        static::current()->info($message);
    }

    /**
     * Logs a debug message.
     *
     * @param mixed $message The message to log as debug.
     *
     * @return void
     */
    public static function debug($message): void
    {
        static::current()->debug($message);
    }

    /**
     * Logs an error message.
     *
     * @param mixed $error The error to log.
     *
     * @return void
     */
    public static function error($error): void
    {
        static::current()->error($error);
    }

    /**
     * Gets the current logger instance from the application.
     *
     * @return Logger The current logger instance.
     */
    private static function current(): Logger
    {
        return Lalaz::$app->logger;
    }
}
