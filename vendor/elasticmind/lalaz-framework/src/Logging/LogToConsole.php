<?php declare(strict_types=1);

namespace Lalaz\Logging;

/**
 * Class LogToConsole
 *
 * This class implements the ILoggerWriter interface to output log messages to the console.
 * It writes messages directly to the standard input stream (stdin).
 *
 * @author  Elasticmind
 * @namespace Lalaz\Logging
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
final class LogToConsole implements ILoggerWriter
{
    /**
     * Writes a log message to the console.
     *
     * @param string $message The log message to output.
     *
     * @return void
     */
    public function write(string $message): void
    {
        $out = fopen('php://stdin', 'w');
        fputs($out, "$message\n");
        fclose($out);
    }
}
