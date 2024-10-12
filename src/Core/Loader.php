<?php declare(strict_types=1);

namespace Lalaz\Core;

use InvalidArgumentException;

/**
 * Class Loader
 *
 * The Loader class provides static methods for loading PHP files from specified directories.
 * It is primarily used for loading function files and ensuring that all necessary functionality
 * is available throughout the application.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Loader
{
    /**
     * Load all files within a specified directory.
     *
     * @param string $directory The path to the directory containing function files.
     * @return void
     */
    public static function loadFiles(string $directory): void
    {
        $directory = rtrim($directory, '/\\');

        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Directory not found: {$directory}");
        }

        $files = glob($directory . '/*.php');

        foreach ($files as $file) {
            require_once $file;
        }
    }

    /**
     * Load all function files from the Functions directory.
     *
     * @return void
     */
    public static function loadCoreFunctions()
    {
        $functionsDirectory = __DIR__ . '/Functions';
        self::loadFiles($functionsDirectory);
    }
}
