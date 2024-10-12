<?php declare(strict_types=1);

namespace Lalaz\IO;

use \Exception;

/**
 * Class Directory
 *
 * This class provides utility methods for handling file system directories.
 * Specifically, it ensures that directories exist before operations on files,
 * creating them if necessary.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Directory
{
    /**
     * Ensures that the directory for the given file path exists.
     * If the directory does not exist, it attempts to create it.
     *
     * @param string $filePath The path of the file or directory to check.
     *                         If the directory does not exist, it will be created.
     *
     * @throws Exception If the directory could not be created.
     *
     * @return void
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
