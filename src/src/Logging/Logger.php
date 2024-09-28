<?php declare(strict_types=1);

namespace Lalaz\Logging;

use Lalaz\Lalaz;

/**
 * Class Logger
 *
 * This class provides logging functionality with support for multiple log writers.
 * It allows writing log messages with different levels (info, debug, error) and
 * directs them to registered log writers.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Logging
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
final class Logger
{
    /** @var array $writers Stores the list of log writers to output the log messages */
    private $writers = array();

    /**
     * Creates a new Logger instance.
     *
     * @return Logger A new Logger instance.
     */
    public static function create(): Logger
    {
        return new Logger();
    }

    /**
     * Adds a log writer to the logger.
     *
     * @param ILoggerWriter $writer The log writer to write messages to.
     * @return Logger The current Logger instance for method chaining.
     */
    public function writeTo(ILoggerWriter $writer): Logger
    {
        $this->writers[] = $writer;
        return $this;
    }

    /**
     * Logs an informational message.
     *
     * @param mixed $message The message to log as info.
     *
     * @return void
     */
    public function info($message): void
    {
        $this->write('INFO', $message);
    }

    /**
     * Logs a debug message.
     *
     * @param mixed $message The message to log as debug.
     *
     * @return void
     */
    public function debug($message): void
    {
        $this->write('DEBUG', $message);
    }

    /**
     * Logs an error message.
     *
     * @param mixed $error The error message to log.
     *
     * @return void
     */
    public function error($error): void
    {
        $this->write('ERROR', $error);
    }

    /**
     * Writes a log message to all registered writers.
     *
     * @param string $level The log level (INFO, DEBUG, ERROR).
     * @param string $message The log message to write.
     *
     * @return void
     */
    private function write(string $level, string $message): void
    {
        $now = date("Y-m-d H:i:s");
        $formattedMessage = "[$now] $level: $message";

        foreach ($this->writers as $writer) {
            $writer->write($formattedMessage);
        }
    }
}
