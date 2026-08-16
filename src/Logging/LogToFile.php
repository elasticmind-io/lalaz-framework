<?php declare(strict_types=1);

namespace Lalaz\Logging;

use RuntimeException;
use Lalaz\Logging\Contracts\LoggerWriterInterface;

/**
 * Class LogToFile
 *
 * Writes log messages to a file with support for automatic log rotation
 * based on file size. Creates directories automatically if they don't exist.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
final class LogToFile implements LoggerWriterInterface
{
    private string $filePath;
    private int $maxFileSize;
    private int $maxFiles;

    /**
     * Constructor for LogToFile.
     *
     * @param string $filePath The full path to the log file.
     * @param int $maxFileSize Maximum file size in bytes before rotation (default: 10MB).
     * @param int $maxFiles Maximum number of rotated files to keep (default: 5).
     */
    public function __construct(
        string $filePath,
        int $maxFileSize = 10485760, // 10MB
        int $maxFiles = 5
    ) {
        $this->filePath = $filePath;
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;

        $this->ensureDirectoryExists();
    }

    /**
     * Writes a log message to the file.
     *
     * @param string $message The message to log to the file.
     * @return void
     * @throws RuntimeException If unable to write to the file.
     */
    public function write(string $message): void
    {
        $this->rotateIfNeeded();

        $result = file_put_contents(
            $this->filePath,
            $message . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($result === false) {
            throw new RuntimeException("Unable to write to log file: {$this->filePath}");
        }
    }

    /**
     * Ensures that the directory for the log file exists.
     *
     * @return void
     * @throws RuntimeException If unable to create the directory.
     */
    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException("Unable to create log directory: {$directory}");
            }
        }

        if (!is_writable($directory)) {
            throw new RuntimeException("Log directory is not writable: {$directory}");
        }
    }

    /**
     * Rotates the log file if it exceeds the maximum size.
     *
     * Renames current log file to filename.1, and shifts existing
     * rotated files (filename.1 -> filename.2, etc.). Deletes the
     * oldest file if maxFiles limit is reached.
     *
     * @return void
     */
    private function rotateIfNeeded(): void
    {
        if (!file_exists($this->filePath)) {
            return;
        }

        $fileSize = filesize($this->filePath);

        if ($fileSize === false || $fileSize < $this->maxFileSize) {
            return;
        }

        // Delete the oldest file if it exists
        $oldestFile = $this->filePath . '.' . $this->maxFiles;
        if (file_exists($oldestFile)) {
            unlink($oldestFile);
        }

        // Shift existing rotated files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $this->filePath . '.' . $i;
            $newFile = $this->filePath . '.' . ($i + 1);

            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }

        // Rotate current file
        rename($this->filePath, $this->filePath . '.1');
    }

    /**
     * Gets the current file path.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Gets the maximum file size before rotation.
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Gets the maximum number of rotated files to keep.
     *
     * @return int
     */
    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }
}
