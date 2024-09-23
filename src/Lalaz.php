<?php declare(strict_types=1);

namespace Lalaz;

use Lalaz\Config\Config;
use Lalaz\Data\Database;
use Lalaz\Logging\Logger;
use Lalaz\Logging\LogToConsole;
use Lalaz\Routing\Router;

/**
 * Class Lalaz
 *
 * This is the core application class that initializes and manages the
 * main components of the Lalaz framework, such as routing, database connections,
 * and logging. It also handles the execution of the application lifecycle.
 *
 * @author  Elasticmind <ola@elasticmind.io>
 * @namespace Lalaz
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class Lalaz
{
    /** @var Lalaz $app The main application instance */
    public static Lalaz $app;

    /** @var string $rootDir The root directory of the application */
    public static string $rootDir;

    /** @var Router $router The router instance responsible for handling routes */
    public Router $router;

    /** @var Database $db The database connection instance */
    public Database $db;

    /** @var Logger $logger The logger instance for logging messages */
    public Logger $logger;

    /**
     * Constructor for the Lalaz application class.
     *
     * Initializes the router, database, and logger. Loads environment variables
     * from the provided root directory.
     *
     * @param string $rootDir The root directory of the application.
     * @param Logger|null $logger Optional logger instance. If null, a default logger is created.
     */
    public function __construct(string $rootDir, ?Logger $logger = null)
    {
        Config::load($rootDir . '/.env');

        $this->router = static::initializeRouter();
        $this->db = static::initializeDb();
        $this->logger = $logger ?? static::initializeDefaultLogger();

        self::$rootDir = $rootDir;
        self::$app = $this;
    }

    /**
     * Runs the application by dispatching the current route.
     *
     * This method uses the router to dispatch the current HTTP request and
     * catch any exceptions that might occur during execution.
     *
     * @return void
     */
    public function run(): void
    {
        try {
            $this->router->dispatch(
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REQUEST_URI']
            );
        } catch (Exception $ex) {
            print($ex);
        }
    }

    /**
     * Initializes the default logger if no logger is provided.
     *
     * @return Logger A default logger that writes logs to the console.
     */
    private static function initializeDefaultLogger(): Logger
    {
        return Logger::create()
            ->writeTo(new LogToConsole());
    }

    /**
     * Initializes the router for handling HTTP requests.
     *
     * @return Router The router instance.
     */
    private static function initializeRouter(): Router
    {
        return new Router();
    }

    /**
     * Initializes the database connection using configuration from the .env file.
     *
     * @return Database The database connection instance.
     */
    public static function initializeDb(): Database
    {
        return new Database([
            'dsn' => Config::get('DB_DSN'),
            'user' => Config::get('DB_USER'),
            'password' => Config::get('DB_PASSWORD')
        ]);
    }

    /**
     * Retrieves a new database connection, ensuring that the environment file is loaded.
     *
     * This method ensures that the `.env` file is loaded and returns a new database connection.
     * If the `.env` file is not found, the application will terminate with an error.
     *
     * @return Database The database connection instance.
     */
    public static function db(): Database
    {
        $files = $migrations = glob('src/App/.env');

        if (count($files) == 0) {
            echo 'No env file found';
            die;
        }

        $envfile = $files[0];

        Config::load($envfile);
        return static::initializeDb();
    }
}
