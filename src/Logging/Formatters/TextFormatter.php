<?php declare(strict_types=1);

namespace Lalaz\Logging\Formatters;

/**
 * Class TextFormatter
 *
 * Formats log messages as plain text strings, including the timestamp, log level,
 * message, and context data. This formatter is suitable for file-based logging
 * and output to plain-text consoles.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class TextFormatter implements FormatterInterface
{
    /**
     * Formats the log message as a text string.
     *
     * Constructs a log string containing the timestamp (formatted as 'Y-m-d H:i:s'),
     * log level, main message, and JSON-encoded context. The log level is converted
     * to uppercase for better readability. If the context is not empty, it will be
     * appended to the message as a JSON string.
     *
     * @param string $level The log level (e.g., 'info', 'error', 'debug').
     * @param string $message The main message to be logged.
     * @param array $context Additional context data that provides more information
     *                       about the log entry, such as exception details, user information, etc.
     *
     * @return string A text-formatted string representing the log entry.
     */
    public function format(string $level, string $message, array $context = []): string
    {
        return sprintf(
            "[%s] %s: %s %s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            json_encode($context)
        );
    }
}
