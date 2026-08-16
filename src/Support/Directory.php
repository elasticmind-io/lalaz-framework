<?php declare(strict_types=1);

namespace Lalaz\Support;

use \Exception;

/**
 * Class Directory
 *
 * Utility class for handling file system directory operations.
 * Provides helper methods to ensure directories exist before file operations,
 * creating them recursively if necessary.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Directory
{
    /**
     * Ensure that the directory for the given file path exists.
     *
     * Checks if the directory exists and creates it recursively with 0755 permissions
     * if it doesn't exist. This is useful before writing files to ensure the directory
     * structure is in place.
     *
     * @param string $filePath The full path to a file. The directory portion will be extracted and created.
     *
     * @throws Exception If the directory creation fails due to permissions or other filesystem issues.
     *
     * @return void
     *
     * @example
     * ```php
     * // Ensure directory exists before writing a file
     * Directory::ensureDirectoryExists('/var/logs/app/error.log');
     * file_put_contents('/var/logs/app/error.log', 'Error message');
     * ```
     */
    public static function ensureDirectoryExists(string $filePath): void
    {
        $directoryPath = dirname($filePath);

        if (!file_exists($directoryPath)) {
            if (!mkdir($directoryPath, 0755, true)) {
                throw new Exception("Failed to create directories: $directoryPath");
            }
        }
    }
}
