<?php declare(strict_types=1);

namespace Lalaz;

use \Throwable;
use \RuntimeException;

use Lalaz\Core\Loader;
use Lalaz\Core\Config;
use Lalaz\Data\Database;
use Lalaz\Event\EventHub;
use Lalaz\Logging\Logger;
use Lalaz\Logging\LogToConsole;
use Lalaz\Routing\Router;
use Lalaz\View\View;

/**
 * Class Lalaz
 *
 * This is the core application class that initializes and manages the
 * main components of the Lalaz framework, such as routing, database connections,
 * and logging. It also handles the execution of the application lifecycle.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Lalaz
{
    /** @var Lalaz|null $instance The main application instance (singleton) */
    private static ?Lalaz $instance = null;

    /** @var string $rootDir The root directory of the application */
    public static string $rootDir;

    /** @var Router $router The router instance responsible for handling routes */
    private Router $router;

    /** @var Database $db The database connection instance */
    public Database $db;

    /** @var EventHub $events The EventHub instance for managing events of the application */
    public EventHub $events;

    /** @var Logger $logger The Logger instance of the application */
    private $logger;

    /**
     * Initializes the singleton instance of the Lalaz application.
     *
     * Loads environment variables, initializes the database, router, and events
     * instances. If already initialized, returns the existing instance.
     *
     * @param string $rootDir The root directory of the application.
     * @param Logger|null $logger Optional logger instance. If null, a default logger is created.
     * @return Lalaz The initialized Lalaz instance.
     */
    public static function initialize(string $rootDir, ?Logger $logger = null): Lalaz
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        Loader::loadCoreFunctions();
        Config::load($rootDir . '/.env');

        $db = static::initializeDb();
        $router = new Router();
        $events = new EventHub();

        self::$instance = new self($rootDir, $logger, $db, $router, $events);
        self::configureRoutes();

        return self::$instance;
    }

    /**
     * Returns the singleton instance of the Lalaz application.
     *
     * Ensures that an instance of Lalaz is initialized before returning it.
     * If no instance is found, initializes one using the default path 'src/App'.
     *
     * @return Lalaz The singleton instance of the application.
     */
    public static function getInstance(): Lalaz
    {
        if (self::$instance === null) {
            self::initialize('src/App');
        }

        return self::$instance;
    }

    /**
     * Constructor for the Lalaz application class.
     *
     * Sets the root directory, logger, database, router, and event manager.
     * It is private to ensure that only one instance (singleton) is created via the initialize method.
     *
     * @param string $rootDir The root directory of the application.
     * @param Logger|null $logger Optional logger instance.
     * @param Database|null $db Optional database instance.
     * @param Router|null $router Optional router instance.
     * @param EventHub|null $events Optional event hub instance.
     */
    private function __construct(
        string $rootDir,
        ?Logger $logger = null,
        ?Database $db = null,
        ?Router $router = null,
        ?EventHub $events = null)
    {
        self::$rootDir = $rootDir;
        $this->logger = $logger ?? static::initializeDefaultLogger();
        $this->db = $db;
        $this->router = $router;
        $this->events = $events;
    }

    /**
     * Runs the application by dispatching the current route.
     *
     * Uses the router to dispatch the current HTTP request and catch any exceptions
     * that might occur during execution, logging them through the logger.
     *
     * @return void
     */
    public function run(): void
    {
        ob_start();

        list(, $error) = tryCatch(function () {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES, 'UTF-8');
            $this->router->dispatch($method, $uri);
        });

        if ($error) {
            View::renderError([], $error);
        }

        ob_end_flush();

        exit();
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
     * Configures the application routes by calling a user-defined function.
     *
     * If a function called `onRouterInitialized` exists, it is executed to allow
     * custom route definitions.
     *
     * @return void
     */
    private static function configureRoutes(): void
    {
        if (function_exists('onRouterInitialized')) {
            onRouterInitialized(self::getInstance()->router);
        }
    }

    /**
     * Initializes the database connection using configuration from the .env file.
     *
     * Retrieves database configuration variables and initializes a database connection.
     * Throws an exception if any required variable is missing.
     *
     * @return Database The database connection instance.
     * @throws RuntimeException If required database configuration variables are missing.
     */
    private static function initializeDb(): Database
    {
        $dsn = Config::get('DB_DSN');
        $user = Config::get('DB_USER');
        $password = Config::get('DB_PASSWORD');

        if (!$dsn) {
            throw new RuntimeException('Database configuration variable DB_DSN is missing.');
        }

        if (!$user) {
            throw new RuntimeException('Database configuration variable DB_USER is missing.');
        }

        if (!$password) {
            throw new RuntimeException('Database configuration variable DB_PASSWORD is missing.');
        }

        return new Database([
            'dsn' => $dsn,
            'user' => $user,
            'password' => $password,
        ]);
    }

    /**
     * Retrieves a new database connection, ensuring that the environment file is loaded.
     *
     * This method ensures that the `.env` file is loaded and returns a new database connection.
     * If the `.env` file is not found, the application will throw an exception.
     *
     * @return Database The database connection instance.
     * @throws RuntimeException If the `.env` file is not found in the expected location.
     */
    public static function db(): Database
    {
        $envfile = 'src/App/.env';

        if (!file_exists($envfile)) {
            throw new RuntimeException('No .env file found in the expected location');
        }

        Config::load($envfile);
        return static::initializeDb();
    }

    /**
     * Retrieves the router instance for the application.
     *
     * This method provides access to the application's router, allowing
     * for registering routes, groups, and middleware.
     *
     * @return Router The router instance.
     */
    public static function router(): Router
    {
        return Lalaz::getInstance()->router;
    }

    /**
     * Retrieve the logger instance from the Lalaz framework.
     *
     * This method provides access to the logger instance, allowing logging of messages, errors, and other information.
     * It utilizes the singleton instance of the Lalaz framework to retrieve the logger.
     *
     * @return mixed The logger instance used by the Lalaz framework.
     */
    public static function logger()
    {
        return Lalaz::getInstance()->logger;
    }
}
