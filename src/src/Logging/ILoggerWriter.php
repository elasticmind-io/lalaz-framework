<?php declare(strict_types=1);

namespace Lalaz\Logging;

/**
 * Interface ILoggerWriter
 *
 * This interface defines a contract for writing log messages.
 * Any class implementing this interface must provide a `write()` method to handle log output.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Logging
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
interface ILoggerWriter
{
    /**
     * Writes a log message.
     *
     * @param string $message The log message to be written.
     *
     * @return void
     */
    public function write(string $message): void;
}
