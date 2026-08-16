<?php declare(strict_types=1);

namespace Lalaz\Logging;

use Stringable;
use Lalaz\Lalaz;

/**
 * Class Log
 *
 * PSR-3 compliant facade for logging messages at different severity levels.
 * Provides static methods for all PSR-3 log levels (emergency, alert, critical,
 * error, warning, notice, info, debug) and routes them to the application's logger instance.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class Log
{
    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function emergency(string|Stringable $message, array $context = []): void
    {
        static::current()->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function alert(string|Stringable $message, array $context = []): void
    {
        static::current()->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function critical(string|Stringable $message, array $context = []): void
    {
        static::current()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function error(string|Stringable $message, array $context = []): void
    {
        static::current()->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function warning(string|Stringable $message, array $context = []): void
    {
        static::current()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function notice(string|Stringable $message, array $context = []): void
    {
        static::current()->notice($message, $context);
    }

    /**
     * Logs an informational message.
     *
     * @param string|Stringable $message The message to log as info.
     * @param array<string, mixed> $context Additional context data.
     *
     * @return void
     */
    public static function info(string|Stringable $message, array $context = []): void
    {
        static::current()->info($message, $context);
    }

    /**
     * Logs a debug message.
     *
     * @param string|Stringable $message The message to log as debug.
     * @param array<string, mixed> $context Additional context data.
     *
     * @return void
     */
    public static function debug(string|Stringable $message, array $context = []): void
    {
        static::current()->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function log($level, string|Stringable $message, array $context = []): void
    {
        static::current()->log($level, $message, $context);
    }

    /**
     * Gets the current logger instance from the application.
     *
     * @return Logger The current logger instance.
     */
    private static function current(): Logger
    {
        return Lalaz::logger();
    }
}
