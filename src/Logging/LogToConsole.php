<?php declare(strict_types=1);

namespace Lalaz\Logging;

use Lalaz\Logging\Contracts\LoggerWriterInterface;

/**
 * Class LogToConsole
 *
 * Efficiently writes log messages to the console (stdout).
 * Uses a persistent stream handle for better performance.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class LogToConsole implements LoggerWriterInterface
{
    /** @var resource|null The output stream handle */
    private $stream = null;

    /**
     * Constructor - opens the stdout stream.
     */
    public function __construct()
    {
        $this->stream = fopen('php://stdout', 'w');

        if (!$this->stream) {
            throw new \RuntimeException('Failed to open stdout stream for logging');
        }
    }

    /**
     * Writes a message to the console.
     *
     * @param string $message The message to log to the console.
     * @return void
     */
    public function write(string $message): void
    {
        if ($this->stream) {
            fwrite($this->stream, $message . PHP_EOL);
        }
    }

    /**
     * Destructor - closes the stream handle.
     */
    public function __destruct()
    {
        if ($this->stream) {
            fclose($this->stream);
            $this->stream = null;
        }
    }
}
