<?php declare(strict_types=1);

namespace Lalaz\Config;

/**
 * Class Config
 *
 * This class provides functionality to load environment variables from a file and retrieve them.
 * It reads key-value pairs from an environment file and stores them in the `$_ENV` superglobal array.
 * It also caches the loaded variables in a static property to prevent reloading.
 *
 * @package Lalaz\Config
 */
class Config
{
    /**
     * @var array|null Stores the loaded environment variables.
     */
    private static $env;

    /**
     * Loads environment variables from a specified file.
     *
     * Reads the environment file line by line, parsing key-value pairs separated by a delimiter,
     * and stores them in the `$_ENV` superglobal array. Caches the loaded variables in the static
     * property `$env` to prevent multiple loads.
     *
     * @param string $envFile The path to the environment file.
     * @return void
     */
    public static function load(string $envFile): void
    {
        if (empty(self::$env)) {
            if (is_file($envFile)) {
                $file = new \SplFileObject($envFile);
                $delimiter = '=';

                while (!$file->eof()) {
                    $contents = trim($file->fgets());

                    if (strpos($contents, $delimiter) !== false) {
                        [$key, $value] = explode($delimiter, $contents, 2);
                        $_ENV[$key] = $value;
                    }
                }
            }
        }

        self::$env = $_ENV;
    }

    /**
     * Retrieves the value of a specified environment variable.
     *
     * Looks up the value of the given key in the loaded environment variables.
     *
     * @param string $key The name of the environment variable to retrieve.
     * @return mixed The value of the environment variable, or null if not found.
     */
    public static function get(string $key): mixed
    {
        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }

        return null;
    }
}
