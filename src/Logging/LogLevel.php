<?php declare(strict_types=1);

namespace Lalaz\Logging;

/**
 * Class LogLevel
 *
 * Defines PSR-3 compatible log levels and their numeric priorities.
 * Higher priority means more severe.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class LogLevel
{
    public const EMERGENCY = 'EMERGENCY';
    public const ALERT = 'ALERT';
    public const CRITICAL = 'CRITICAL';
    public const ERROR = 'ERROR';
    public const WARNING = 'WARNING';
    public const NOTICE = 'NOTICE';
    public const INFO = 'INFO';
    public const DEBUG = 'DEBUG';

    /**
     * @var array<string, int> Mapping of log levels to numeric priorities
     */
    private const PRIORITIES = [
        self::DEBUG => 100,
        self::INFO => 200,
        self::NOTICE => 250,
        self::WARNING => 300,
        self::ERROR => 400,
        self::CRITICAL => 500,
        self::ALERT => 550,
        self::EMERGENCY => 600,
    ];

    /**
     * Gets the numeric priority for a log level.
     *
     * @param string $level The log level name.
     * @return int The numeric priority (higher = more severe).
     */
    public static function getPriority(string $level): int
    {
        $levelUpper = strtoupper($level);
        return self::PRIORITIES[$levelUpper] ?? self::PRIORITIES[self::DEBUG];
    }

    /**
     * Checks if a log level should be logged based on minimum level.
     *
     * @param string $level The level of the message to log.
     * @param string $minLevel The minimum level threshold.
     * @return bool True if the message should be logged.
     */
    public static function shouldLog(string $level, string $minLevel): bool
    {
        return self::getPriority($level) >= self::getPriority($minLevel);
    }

    /**
     * Gets all available log levels ordered by severity (lowest to highest).
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::DEBUG,
            self::INFO,
            self::NOTICE,
            self::WARNING,
            self::ERROR,
            self::CRITICAL,
            self::ALERT,
            self::EMERGENCY,
        ];
    }
}
