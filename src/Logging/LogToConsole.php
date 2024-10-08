<?php declare(strict_types=1);

namespace Lalaz\Logging;

/**
 * Class LogToConsole
 *
 * This class implements the ILoggerWriter interface to output log messages to the console.
 * It writes messages directly to the standard input stream (stdin).
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class LogToConsole implements LoggerWriterInterface
{
    /**
     * Writes a message to the console.
     *
     * @param string $message The message to log to the console.
     * @return void
     */
    public function write(string $message): void
    {
        $stream = fopen('php://stdout', 'w');

        if ($stream && is_resource($stream)) {
            fputs($stream, $message . PHP_EOL);
            fclose($stream);
        }
    }
}
