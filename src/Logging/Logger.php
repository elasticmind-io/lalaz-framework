<?php declare(strict_types=1);

namespace Lalaz\Logging;

use Lalaz\Lalaz;
use Lalaz\Core\Config;
use Lalaz\Logging\Formatters\FormatterInterface;
use Lalaz\Logging\Formatters\TextFormatter;

/**
 * Class Logger
 *
 * This class provides logging functionality with support for multiple log writers.
 * It allows writing log messages with different levels (info, debug, error) and
 * directs them to registered log writers.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class Logger
{
    /** @var array $writers Stores the list of log writers to output the log messages */
    private $writers = array();

    protected FormatterInterface $formatter;

    protected function __construct(FormatterInterface $formatter = new TextFormatter())
    {
        $this->formatter = $formatter;
    }

    /**
     * Creates a new Logger instance.
     *
     * @return Logger A new Logger instance.
     */
    public static function create(FormatterInterface $formatter = new TextFormatter()): Logger
    {
        return new Logger($formatter);
    }

    /**
     * Adds a log writer to the logger.
     *
     * @param LoggerWriterInterface $writer The log writer to write messages to.
     * @return Logger The current Logger instance for method chaining.
     */
    public function writeTo(LoggerWriterInterface $writer): Logger
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
    public function info($message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Logs a debug message.
     *
     * @param mixed $message The message to log as debug.
     *
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        if (Config::isDebug()) {
            $this->log('DEBUG', $message, $context);
        }
    }

    /**
     * Logs an error message.
     *
     * @param mixed $error The error message to log.
     *
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $formattedMessage = $this->formatter->format($level, $message, $context);
        $this->write($formattedMessage);
    }

    /**
     * Writes a log message to all registered writers.
     *
     * @param string $level The log level (INFO, DEBUG, ERROR).
     * @param string $message The log message to write.
     *
     * @return void
     */
    private function write(string $message): void
    {
        foreach ($this->writers as $writer) {
            $writer->write($message);
        }
    }
}
