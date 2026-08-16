<?php declare(strict_types=1);

namespace Lalaz;

use \RuntimeException;
use Lalaz\Core\Loader;
use Lalaz\Core\Config;
use Lalaz\Data\Database;
use Lalaz\Data\Adapters\ConnectionAdapterResolver;
use Lalaz\Events\EventHub;
use Lalaz\Logging\Logger;
use Lalaz\Logging\LogToConsole;
use Lalaz\Routing\Router;
use Lalaz\View\View;
use Lalaz\View\TemplateEngine;

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

    /** @var string $appDirectory The root directory of the application */
    private static string $appDirectory;

    /** @var Logger $logger The Logger instance of the application */
    private Logger $logger;

    /** @var Router $router The router instance responsible for handling routes */
    private Router $router;

    /** @var Database $db The database connection instance */
    private Database $db;

    /** @var EventHub $events The EventHub instance for managing events of the application */
    private EventHub $events;

    /**
     * Initializes the singleton instance of the Lalaz application.
     *
     * Loads environment variables, initializes the database, router, and events
     * instances. If already initialized, returns the existing instance.
     *
     * @param string $appDirectory The root directory of the application.
     * @param Logger|null $logger Optional logger instance. If null, a default logger is created.
     * @return Lalaz The initialized Lalaz instance.
     */
    public static function initialize(string $appDirectory, ?Logger $logger = null): Lalaz
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new self($appDirectory, $logger);

        Loader::loadCoreFunctions();
        Config::load($appDirectory . '/.env');
        Loader::loadAppConfiguration();

        debug('Initialize Lalaz App');

        self::$instance->router = static::initializeRouter();
        self::$instance->events = static::initializeEventHub();
        self::$instance->db = static::initializeDb();

        static::configureRoutes();

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
            self::initialize('src/App', null);
        }

        return self::$instance;
    }

    /**
     * Constructor for the Lalaz application class.
     *
     * Sets the root directory, logger, database, router, and event manager.
     * It is private to ensure that only one instance (singleton) is created via the initialize method.
     *
     * @param string $appDirectory The root directory of the application.
     * @param Logger|null $logger Optional logger instance.
     * @param Database|null $db Optional database instance.
     * @param Router|null $router Optional router instance.
     * @param EventHub|null $events Optional event hub instance.
     */
    private function __construct(string $appDirectory, ?Logger $logger = null)
    {
        self::$appDirectory = $appDirectory;
        $this->logger = $logger ?? static::initializeDefaultLogger();
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
        TemplateEngine::init();

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
     * Da a aplicacao a chance de registrar as rotas dela.
     *
     * O gancho foi removido em algum ponto da develop e nada ocupou o lugar: a
     * varredura de atributos do Router cobre outro caso de uso, nao este. Toda
     * aplicacao que declara rotas em Config/routes.php via onRouterInitialized()
     * — o esqueleto padrao do framework — passava a subir com ZERO rota, e cada
     * pagina respondia 404 sem nenhum erro que apontasse a causa.
     *
     * @return void
     */
    private static function configureRoutes(): void
    {
        debug('Configuring App Routes');

        if (function_exists('onRouterInitialized')) {
            onRouterInitialized();
        }
    }

    private static function initializeRouter(): Router
    {
        debug('Initializing App Router');
        return new Router();
    }

    private static function initializeEventHub(): EventHub
    {
        debug('Initializing App EventHub');
        return new EventHub();
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
        debug('Resolving database adapter...');
        $adapter = ConnectionAdapterResolver::resolve();
        return new Database($adapter);
    }

    public static function appDirectory(): string
    {
        return self::$appDirectory;
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
    public static function createStandaloneDbInstance(): Database
    {
        $appDirectory = 'src/App';
        $envfile = "{$appDirectory}/.env";
        Config::load($envfile);
        Loader::loadCoreFunctions();
        return static::initializeDb();
    }

    /**
     * Retrieves the db instance for the application.
     *
     * @return Database The database connection instance.
     */
    public static function db(): Database
    {
        return static::getInstance()->db;
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
        return static::getInstance()->router;
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
        return static::getInstance()->logger;
    }
}
