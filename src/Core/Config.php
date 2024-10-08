<?php declare(strict_types=1);

namespace Lalaz\Core;

/**
 * Class Config
 *
 * This class provides functionality to load environment variables from a file and retrieve them.
 * It reads key-value pairs from an environment file and stores them in the `$_ENV` superglobal array.
 * It also caches the loaded variables in a static property to prevent reloading.
 *
 * Supports different delimiters, ignores comments, provides type casting for variables,
 * allows setting default values, and validates the loaded variables.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Config
{
    /**
     * @var array|null Stores the loaded environment variables as key-value pairs.
     */
    private static ?array $env = null;

    /**
     * @var int|null Stores the last modified time of the environment file.
     */
    private static ?int $lastModifiedTime = null;

    /**
     * Loads environment variables from a specified file.
     *
     * Reads the environment file line by line, parsing key-value pairs separated by a delimiter.
     * Stores them in the `$_ENV` superglobal array and caches them to prevent multiple loads.
     * Supports ignoring comments and processing variable substitutions using the `${VAR_NAME}` syntax.
     *
     * @param string $envFile The path to the environment file.
     * @param string $delimiter The delimiter used to separate keys and values (default: '=').
     * @param bool $forceReload Whether to force reloading the environment variables, ignoring cache (default: false).
     * @return void
     */
    public static function load(string $envFile, string $delimiter = '=', bool $forceReload = false): void
    {
        if ($forceReload || self::shouldReload($envFile)) {
            if (is_file($envFile)) {
                $file = new \SplFileObject($envFile);

                while (!$file->eof()) {
                    $contents = trim($file->fgets());

                    // Skip empty lines and comments
                    if (empty($contents) || strpos($contents, '#') === 0 || strpos($contents, ';') === 0) {
                        continue;
                    }

                    // Parse key-value pairs
                    if (strpos($contents, $delimiter) !== false) {
                        [$key, $value] = explode($delimiter, $contents, 2);
                        $key = trim($key);
                        $value = trim($value);

                        // Substitute ${VAR_NAME} variables in the value
                        $value = preg_replace_callback('/\${([A-Z_]+)}/', function ($matches) {
                            return $_ENV[$matches[1]] ?? $matches[0];
                        }, $value);

                        // Set environment variable
                        if (getenv($key) !== false) {
                            $_ENV[$key] = getenv($key);
                        } else {
                            $_ENV[$key] = $value;
                        }
                    }
                }

                self::$env = $_ENV;
                self::$lastModifiedTime = filemtime($envFile);
            }
        }
    }

    /**
     * Determines if the environment variables should be reloaded.
     *
     * Compares the last modified time of the environment file with the cached value
     * to determine if the variables should be reloaded.
     *
     * @param string $envFile The path to the environment file.
     * @return bool True if the environment file was modified since the last load, false otherwise.
     */
    private static function shouldReload(string $envFile): bool
    {
        $currentModifiedTime = filemtime($envFile);
        return self::$env === null || self::$lastModifiedTime !== $currentModifiedTime;
    }

    /**
     * Retrieves the value of a specified environment variable.
     *
     * Looks up the value of the given key in the loaded environment variables.
     * If the key does not exist, returns the provided default value or null.
     *
     * @param string $key The name of the environment variable to retrieve.
     * @param mixed $default The default value to return if the variable is not found (default: null).
     * @return mixed The value of the environment variable, or the default value if not found.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$env[$key] ?? $_ENV[$key] ?? $default;
    }

    /**
     * Retrieves a typed environment variable.
     *
     * Retrieves the environment variable as a specific type (string, int, bool, or float).
     * Returns the provided default value if the variable is not found.
     *
     * @param string $key The name of the environment variable to retrieve.
     * @param mixed $default The default value to return if the variable is not found (default: null).
     * @param string $type The expected type of the variable ('string', 'int', 'bool', 'float') (default: 'string').
     * @return mixed The typed value of the environment variable, or the default value if not found.
     */
    public static function getTyped(string $key, mixed $default = null, string $type = 'string'): mixed
    {
        $value = self::get($key, $default);

        return match ($type) {
            'int' => is_numeric($value) ? (int) $value : $default,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default,
            'float' => is_numeric($value) ? (float) $value : $default,
            default => (string) $value,
        };
    }

    /**
     * Validates the environment variable using a callback function.
     *
     * Checks the value of the specified environment variable against a provided
     * validator function, which returns true if valid.
     *
     * @param string $key The name of the environment variable to validate.
     * @param callable $validator A callback function that returns true if the value is valid.
     * @return bool True if valid, false otherwise.
     */
    public static function validate(string $key, callable $validator): bool
    {
        $value = self::get($key);
        return isset($value) && $validator($value);
    }

    /**
     * Clears the cached environment variables.
     *
     * This method is useful for testing or reloading the environment variables
     * when the `.env` file changes. Clears both the variable cache and the last
     * modified time.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$env = null;
        self::$lastModifiedTime = null;
    }

    /**
     * Returns all the loaded environment variables.
     *
     * Retrieves all the environment variables currently loaded in memory.
     *
     * @return array The array of all loaded environment variables.
     */
    public static function all(): array
    {
        return array_merge(self::$env ?? [], $_ENV);
    }

    /**
     * Checks if the current environment matches the specified value.
     *
     * @param string $env The environment to compare with.
     * @return bool True if it matches, false otherwise.
     */
    public static function isEnv(string $env): bool
    {
        $currentEnv = Config::get('ENV', 'development');
        return strcasecmp($currentEnv, $env) === 0;
    }

    /**
     * Checks if the current environment is set to 'debug'.
     *
     * @return bool True if the environment is 'debug', false otherwise.
     */
    public static function isDebug()
    {
        return self::isEnv('debug');
    }

    /**
     * Checks if the current environment is set to 'development'.
     *
     * @return bool True if the environment is 'development', false otherwise.
     */
    public static function isDevelopment()
    {
        return self::isEnv('development');
    }

    /**
     * Checks if the current environment is set to 'production'.
     *
     * @return bool True if the environment is 'production', false otherwise.
     */
    public static function isProduction(): bool
    {
        return self::isEnv('production');
    }
}
