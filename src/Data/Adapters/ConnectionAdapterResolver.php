<?php declare(strict_types=1);

namespace Lalaz\Data\Adapters;

use RuntimeException;
use Lalaz\Core\Config;
use Lalaz\Data\Contracts\ConnectionAdapterInterface;

/**
 * Class ConnectionAdapterResolver
 *
 * Resolves the appropriate ConnectionAdapterInterface implementation
 * based on configuration or runtime context.
 *
 * @package elasticmind\lalaz-framework
 */
class ConnectionAdapterResolver
{
    /**
     * Returns the resolved adapter based on the DB_PROVIDER config.
     *
     * @return ConnectionAdapterInterface
     *
     * @throws RuntimeException If required config is missing or unsupported provider is set.
     */
    public static function resolve(): ConnectionAdapterInterface
    {
        $provider = Config::get('DB_PROVIDER', 'sqlite');

        switch (strtolower($provider)) {
            case 'sqlite':
                $path = Config::get('SQLITE_PATH');

                if (!$path) {
                    throw new RuntimeException('SQLITE_PATH is required when DB_PROVIDER=sqlite');
                }

                return new SQLiteAdapter(['path' => $path]);

            case 'mysql':
                $host       = Config::get('DB_HOST');
                $port       = Config::get('DB_PORT');
                $database   = Config::get('DB_NAME');
                $user       = Config::get('DB_USER');
                $password   = Config::get('DB_PASSWORD');

                if (!$host || !$port  || !$database || !$user || !$password) {
                    throw new RuntimeException('Incomplete MySQL database configuration.');
                }

                return new MysqlAdapter([
                    'host'     => $host,
                    'port'     => $port,
                    'database' => $database,
                    'user'     => $user,
                    'password' => $password,
                ]);

            case 'pgsql':
            case 'postgres':
            case 'postgresql':
                $host       = Config::get('DB_HOST');
                $port       = Config::get('DB_PORT');
                $database   = Config::get('DB_NAME');
                $user       = Config::get('DB_USER');
                $password   = Config::get('DB_PASSWORD');
                $schema     = Config::get('DB_SCHEMA', 'public');

                if (!$host || !$port || !$database || !$user || !$password) {
                    throw new RuntimeException('Incomplete PostgreSQL database configuration.');
                }

                return new PostgresAdapter([
                    'host'     => $host,
                    'port'     => $port,
                    'database' => $database,
                    'user'     => $user,
                    'password' => $password,
                    'schema'   => $schema,
                ]);

            case 'dbless':
            case 'none':
                return new DbLessAdapter();

            default:
                throw new RuntimeException("Unsupported DB_PROVIDER: '$provider'");
        }
    }
}
