<?php declare(strict_types=1);

namespace Lalaz\Logging;

/**
 * Interface LoggerWriterInterface
 *
 * This interface defines a contract for writing log messages.
 * Any class implementing this interface must provide a `write()` method to handle log output.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
interface LoggerWriterInterface
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
