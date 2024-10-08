<?php declare(strict_types=1);

namespace Lalaz\Logging\Formatters;

/**
 * Interface FormatterInterface
 *
 * Provides a contract for log message formatters, ensuring a consistent way
 * to format log messages across different log levels and contexts.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
interface FormatterInterface
{
    /**
     * Formats the log message based on the given level and context.
     *
     * This method should implement a formatting strategy that incorporates the log level,
     * message, and context data into a single formatted string. The formatting strategy may vary,
     * such as plain text, JSON, or other structured formats, depending on the implementation.
     *
     * @param string $level The log level (e.g., 'info', 'error', 'debug').
     * @param string $message The log message to be formatted.
     * @param array $context The additional context data to be merged into the message.
     *                       Context can contain any auxiliary information such as
     *                       exception details, user information, or metadata relevant to the log entry.
     * @return string The formatted log message, ready to be output or stored.
     */
    public function format(string $level, string $message, array $context = []): string;
}
