<?php declare(strict_types=1);

namespace Lalaz\Logging;

use Psr\Log\LoggerInterface;
use Stringable;
use Lalaz\Logging\Contracts\FormatterInterface;
use Lalaz\Logging\Contracts\LoggerWriterInterface;
use Lalaz\Logging\Formatters\TextFormatter;

/**
 * Class Logger
 *
 * PSR-3 compliant logger with support for multiple log writers and formatters.
 * Provides logging functionality for all PSR-3 log levels (emergency, alert, critical,
 * error, warning, notice, info, debug) and directs them to registered log writers.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class Logger implements LoggerInterface
{
    /** @var LoggerWriterInterface[] Stores the list of log writers to output the log messages */
    private array $writers = [];

    private FormatterInterface $formatter;

    private string $minLevel = LogLevel::DEBUG;

    protected function __construct(?FormatterInterface $formatter = null, ?string $minLevel = null)
    {
        $this->formatter = $formatter ?? new TextFormatter();

        if ($minLevel !== null) {
            $this->minLevel = strtoupper($minLevel);
        }
    }

    /**
     * Creates a new Logger instance.
     *
     * @param FormatterInterface|null $formatter Optional formatter for log messages.
     * @param string|null $minLevel Optional minimum log level (default: DEBUG).
     * @return Logger A new Logger instance.
     */
    public static function create(?FormatterInterface $formatter = null, ?string $minLevel = null): Logger
    {
        return new Logger($formatter, $minLevel);
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
     * @param string|Stringable $message The message to log as info.
     * @param array<string, mixed> $context Additional context data.
     *
     * @return void
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Logs a debug message.
     *
     * @param string|Stringable $message The message to log as debug.
     * @param array<string, mixed> $context Additional context data.
     *
     * @return void
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Logs an error message.
     *
     * @param string|Stringable $message The error message to log.
     * @param array<string, mixed> $context Additional context data.
     *
     * @return void
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
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
    public function log($level, string|Stringable $message, array $context = []): void
    {
        // Check if this level should be logged based on minimum level
        if (!LogLevel::shouldLog((string) $level, $this->minLevel)) {
            return;
        }

        $formattedMessage = $this->formatter->format(
            (string) $level,
            $this->interpolate((string) $message, $context),
            $context
        );
        $this->write($formattedMessage);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * Replaces {key} placeholders in the message with values from context array.
     * This is part of PSR-3 specification.
     *
     * @param string $message The message with placeholders.
     * @param array<string, mixed> $context Context data for interpolation.
     *
     * @return string The interpolated message.
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            // Check that the value can be cast to string
            if (is_null($value) || is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Writes a log message to all registered writers.
     *
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

    /**
     * Sets the minimum log level threshold.
     *
     * Only messages with equal or higher severity will be logged.
     *
     * @param string $level The minimum level (e.g., 'WARNING', 'ERROR').
     * @return self
     */
    public function setMinLevel(string $level): self
    {
        $this->minLevel = strtoupper($level);
        return $this;
    }

    /**
     * Gets the current minimum log level.
     *
     * @return string
     */
    public function getMinLevel(): string
    {
        return $this->minLevel;
    }

    /**
     * Gets the current formatter.
     *
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    /**
     * Sets a new formatter.
     *
     * @param FormatterInterface $formatter
     * @return self
     */
    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }
}
