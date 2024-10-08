<?php declare(strict_types=1);

namespace Lalaz\Logging\Formatters;

/**
 * Class JsonFormatter
 *
 * Formats log messages as JSON strings, including the timestamp, log level,
 * message, and context data. This formatter is useful for structured logging
 * and easy parsing by log aggregation tools.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class JsonFormatter implements FormatterInterface
{
    /**
     * Formats the log message as a JSON string.
     *
     * Constructs an associative array containing the timestamp, log level,
     * message, and context, then converts it to a JSON string. The timestamp is
     * in ISO 8601 format ('c'). If JSON encoding fails, throws a RuntimeException
     * with the error message.
     *
     * @param string $level The log level (e.g., 'info', 'error', 'debug').
     * @param string $message The main message to be logged.
     * @param array $context Additional context data that provides more information
     *                       about the log entry, such as exception details, user information, etc.
     *
     * @return string A JSON-encoded string representing the log entry.
     */
    public function format(string $level, string $message, array $context = []): string
    {
        return json_encode([
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
