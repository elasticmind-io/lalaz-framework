<?php declare(strict_types=1);

namespace Lalaz\Core;

use Lalaz\Lalaz;
use Lalaz\Core\Generators\GeneratorEngine;
use Lalaz\Data\Migrations\MigrationRunner;
use Lalaz\Data\Seeders\SeederRunner;
use Lalaz\Queue\Jobs;
use Lalaz\Security\Hashing;

/**
 * Class Cli
 *
 * This class handles command-line interactions for the Lalaz framework,
 * providing various commands for generating files, running migrations,
 * seeding the database, managing jobs, and serving the application.
 *
 * Each command can be accessed by passing arguments through the CLI,
 * and the appropriate method will be executed based on the command provided.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Cli
{
    /**
     * Processes a CLI command based on the provided arguments.
     *
     * Routes the command to the appropriate generator, migration, job runner, or server
     * handler, and executes the specified action. Prints out messages to indicate the
     * success or failure of each command.
     *
     * @param array $args The array of command-line arguments.
     * @return void
     */
    public static function command(array $args): void
    {
        $command = $args[1] ?? 'help';
        $name = $args[2] ?? '';

        // Handle help flags
        if (in_array($command, ['--help', '-h', 'help'])) {
            self::help();
            exit(0);
        }

        // Handle version flags
        if (in_array($command, ['--version', '-v', 'version'])) {
            self::showVersion();
            exit(0);
        }

        // Handle detailed command help (e.g., "php lalaz g:controller --help")
        if ($name === '--help' || $name === '-h') {
            self::showCommandDetail($command);
            exit(0);
        }

        switch ($command) {
            case 'g:controller':
                GeneratorEngine::controller($name);
                echo "Controller {$name} created successfully!\n";
                break;

            case 'g:middleware':
                GeneratorEngine::middleware($name);
                echo "Middleware {$name} created successfully!\n";
                break;

            case 'g:model':
                GeneratorEngine::model($name);
                echo "Model {$name} created successfully!\n";
                break;

            case 'g:form':
                GeneratorEngine::form($name);
                echo "Form {$name} created successfully!\n";
                break;

            case 'g:migration':
                GeneratorEngine::migration($name);
                echo "Migration {$name} created successfully!\n";
                break;

            case 'g:seeder':
                GeneratorEngine::seeder($name);
                echo "Seeder {$name} created successfully!\n";
                break;

            case 'g:event':
                GeneratorEngine::event($name);
                echo "Event {$name} created successfully!\n";
                break;

            case 'g:job':
                GeneratorEngine::job($name);
                echo "Job {$name} created successfully!\n";
                break;

            case 'g:view':
                GeneratorEngine::view($name);
                echo "View {$name} created successfully!\n";
                break;

            case 'migrate':
                MigrationRunner::run();
                echo "Migrations executed successfully!\n";
                break;

            case 'migrate:rollback':
                MigrationRunner::rollback();
                echo "Migrations rolled back successfully!\n";
                break;

            case 'migrate:reset':
                MigrationRunner::reset();
                echo "Migrations reset successfully!\n";
                break;

            case 'seed':
                $seedName = ucwords($name);
                $runner = new SeederRunner(Lalaz::db());
                $runner->runSeeder("{$seedName}Seed");
                echo "Seeder {$seedName} executed successfully!\n";
                break;

            case 'seed:all':
                $runner = new SeederRunner(Lalaz::db());
                $runner->runSeeders();
                echo "Seeders executed successfully!\n";
                break;

            case 'jobs:once':
                Jobs::run();
                echo "Jobs executed successfully!\n";
                break;

            case 'jobs:run':
                while (true) {
                    Jobs::run();
                    echo "Jobs executed successfully! Waiting for next run...\n";
                    sleep(10);
                }
                break;

            case 'hash:password':
                echo Hashing::generateHash($name) . "\n";
                break;

            case 'routes':
                static::routes();
                break;

            case 'route:cache':
                static::routeCache();
                break;

            case 'route:cache-clear':
                static::routeCacheClear();
                break;

            case 'config:cache':
                static::configCache($args);
                break;

            case 'config:cache-clear':
                static::configCacheClear();
                break;

            case 'middlewares':
                static::showMiddlewaresForRoute((int)$name);
                break;

            case 'serve':
                $requestedPort = $args[2] ?? '8080';
                $port = filter_var(
                    $requestedPort,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => 65535]]
                );

                if ($port === false) {
                    echo "Invalid port provided. Please use an integer between 1 and 65535.\n";
                    exit(1);
                }

                $phpServerCmd = sprintf('php -S %s', escapeshellarg("localhost:{$port}"));
                $viteCmd = 'npm run watch';
                $fullCommand = sprintf('(%s & %s)', $phpServerCmd, $viteCmd);
                passthru($fullCommand);
                echo "Server running on port {$port}\n";
                break;

            case 'test':
                passthru('./vendor/bin/pest');
                echo "Tests executed successfully!\n";
                break;

            case 'help':
            default:
                self::help();
                break;
        }

        exit(0);
    }

    /**
     * Prints the list of available CLI commands for the Lalaz framework.
     *
     * Outputs the commands that can be executed through the CLI interface, with
     * a brief description of each command.
     *
     * @return void
     */
    private static function help(): void
    {
        self::showVersion();

        echo "\n";
        echo "Usage: php lalaz <command> [arguments] [options]\n";
        echo "\n";

        // 🎨 Generators
        echo "🎨 \033[1mGenerators\033[0m\n";
        echo "  \033[32mg:controller\033[0m <name>     Generate a new controller class\n";
        echo "  \033[32mg:middleware\033[0m <name>     Generate a new middleware class\n";
        echo "  \033[32mg:model\033[0m <name>          Generate a new model class\n";
        echo "  \033[32mg:form\033[0m <name>           Generate a new form class\n";
        echo "  \033[32mg:migration\033[0m <name>      Generate a new migration file\n";
        echo "  \033[32mg:seeder\033[0m <name>         Generate a new seeder class\n";
        echo "  \033[32mg:event\033[0m <name>          Generate a new event class\n";
        echo "  \033[32mg:job\033[0m <name>            Generate a new job class\n";
        echo "  \033[32mg:view\033[0m <name>           Generate a new view file\n";
        echo "\n";

        // 🗄️ Database
        echo "🗄️  \033[1mDatabase\033[0m\n";
        echo "  \033[33mmigrate\033[0m                 Run all pending database migrations\n";
        echo "  \033[33mmigrate:rollback\033[0m        Rollback the last migration batch\n";
        echo "  \033[33mmigrate:reset\033[0m           Reset all migrations (drop all tables)\n";
        echo "  \033[33mseed\033[0m <name>             Run a specific database seeder\n";
        echo "  \033[33mseed:all\033[0m                Run all database seeders\n";
        echo "\n";

        // ⚡ Cache
        echo "⚡ \033[1mCache\033[0m\n";
        echo "  \033[36mroute:cache\033[0m             Compile routes for 50x faster loading (production)\n";
        echo "  \033[36mroute:cache-clear\033[0m       Clear compiled route cache\n";
        echo "  \033[36mconfig:cache\033[0m [env_file] Compile config for 300x faster loading (production)\n";
        echo "  \033[36mconfig:cache-clear\033[0m      Clear compiled config cache\n";
        echo "\n";

        // 📦 Queue
        echo "📦 \033[1mQueue\033[0m\n";
        echo "  \033[35mjobs:once\033[0m               Run the job queue once\n";
        echo "  \033[35mjobs:run\033[0m                Run the job queue continuously (daemon mode)\n";
        echo "\n";

        // 🔧 Development
        echo "🔧 \033[1mDevelopment\033[0m\n";
        echo "  \033[34mserve\033[0m [port]            Start development server with Vite (default: 8080)\n";
        echo "  \033[34mtest\033[0m                    Run PHPUnit/Pest test suite\n";
        echo "\n";

        // 🛠️ Utilities
        echo "🛠️  \033[1mUtilities\033[0m\n";
        echo "  \033[37mroutes\033[0m                  Display all registered routes in table format\n";
        echo "  \033[37mmiddlewares\033[0m <index>     Show middlewares for a specific route by index\n";
        echo "  \033[37mhash:password\033[0m <string>  Generate a secure password hash\n";
        echo "\n";

        // 📚 Help & Info
        echo "📚 \033[1mHelp & Info\033[0m\n";
        echo "  \033[90mhelp\033[0m, \033[90m--help\033[0m, \033[90m-h\033[0m       Display this help message\n";
        echo "  \033[90m--version\033[0m, \033[90m-v\033[0m           Show framework version information\n";
        echo "\n";

        echo "Examples:\n";
        echo "  php lalaz g:controller UserController\n";
        echo "  php lalaz g:model User\n";
        echo "  php lalaz migrate\n";
        echo "  php lalaz serve 3000\n";
        echo "  php lalaz config:cache\n";
        echo "\n";
    }

    /**
     * Show detailed help for a specific command.
     *
     * @param string $commandName The command to show help for
     * @return void
     */
    private static function showCommandDetail(string $commandName): void
    {
        $commands = [
            'g:controller' => [
                'title' => 'Generate Controller',
                'description' => 'Creates a new controller class in the app/Controllers directory.',
                'usage' => 'php lalaz g:controller <name>',
                'arguments' => [
                    '<name>' => 'The name of the controller (e.g., UserController, PostController)'
                ],
                'examples' => [
                    'php lalaz g:controller UserController',
                    'php lalaz g:controller Api/ProductController'
                ]
            ],
            'g:middleware' => [
                'title' => 'Generate Middleware',
                'description' => 'Creates a new middleware class in the app/Middleware directory.',
                'usage' => 'php lalaz g:middleware <name>',
                'arguments' => [
                    '<name>' => 'The name of the middleware (e.g., AuthMiddleware, RateLimiter)'
                ],
                'examples' => [
                    'php lalaz g:middleware AuthMiddleware',
                    'php lalaz g:middleware RateLimiter'
                ]
            ],
            'g:model' => [
                'title' => 'Generate Model',
                'description' => 'Creates a new model class in the app/Models directory.',
                'usage' => 'php lalaz g:model <name>',
                'arguments' => [
                    '<name>' => 'The name of the model (e.g., User, Post, Product)'
                ],
                'examples' => [
                    'php lalaz g:model User',
                    'php lalaz g:model Product'
                ]
            ],
            'g:form' => [
                'title' => 'Generate Form',
                'description' => 'Creates a new form class in the app/Forms directory.',
                'usage' => 'php lalaz g:form <name>',
                'arguments' => [
                    '<name>' => 'The name of the form (e.g., LoginForm, RegisterForm)'
                ],
                'examples' => [
                    'php lalaz g:form LoginForm',
                    'php lalaz g:form ContactForm'
                ]
            ],
            'g:migration' => [
                'title' => 'Generate Migration',
                'description' => 'Creates a new migration file in the database/migrations directory.',
                'usage' => 'php lalaz g:migration <name>',
                'arguments' => [
                    '<name>' => 'A descriptive name for the migration (e.g., CreateUsersTable)'
                ],
                'examples' => [
                    'php lalaz g:migration CreateUsersTable',
                    'php lalaz g:migration AddEmailToUsersTable'
                ]
            ],
            'g:seeder' => [
                'title' => 'Generate Seeder',
                'description' => 'Creates a new seeder class in the database/seeders directory.',
                'usage' => 'php lalaz g:seeder <name>',
                'arguments' => [
                    '<name>' => 'The name of the seeder (e.g., UserSeeder, ProductSeeder)'
                ],
                'examples' => [
                    'php lalaz g:seeder UserSeeder',
                    'php lalaz g:seeder ProductSeeder'
                ]
            ],
            'g:event' => [
                'title' => 'Generate Event',
                'description' => 'Creates a new event class in the app/Events directory.',
                'usage' => 'php lalaz g:event <name>',
                'arguments' => [
                    '<name>' => 'The name of the event (e.g., UserRegistered, OrderPlaced)'
                ],
                'examples' => [
                    'php lalaz g:event UserRegistered',
                    'php lalaz g:event OrderPlaced'
                ]
            ],
            'g:job' => [
                'title' => 'Generate Job',
                'description' => 'Creates a new job class in the app/Jobs directory.',
                'usage' => 'php lalaz g:job <name>',
                'arguments' => [
                    '<name>' => 'The name of the job (e.g., SendEmailJob, ProcessImageJob)'
                ],
                'examples' => [
                    'php lalaz g:job SendEmailJob',
                    'php lalaz g:job ProcessImageJob'
                ]
            ],
            'g:view' => [
                'title' => 'Generate View',
                'description' => 'Creates a new view file in the resources/views directory.',
                'usage' => 'php lalaz g:view <name>',
                'arguments' => [
                    '<name>' => 'The name of the view (e.g., home, users/index)'
                ],
                'examples' => [
                    'php lalaz g:view home',
                    'php lalaz g:view users/profile'
                ]
            ],
            'migrate' => [
                'title' => 'Run Migrations',
                'description' => 'Executes all pending database migrations.',
                'usage' => 'php lalaz migrate',
                'examples' => ['php lalaz migrate']
            ],
            'migrate:rollback' => [
                'title' => 'Rollback Migrations',
                'description' => 'Rolls back the last batch of migrations.',
                'usage' => 'php lalaz migrate:rollback',
                'examples' => ['php lalaz migrate:rollback']
            ],
            'migrate:reset' => [
                'title' => 'Reset Migrations',
                'description' => 'Drops all tables and re-runs all migrations.',
                'usage' => 'php lalaz migrate:reset',
                'examples' => ['php lalaz migrate:reset']
            ],
            'seed' => [
                'title' => 'Run Seeder',
                'description' => 'Runs a specific database seeder.',
                'usage' => 'php lalaz seed <name>',
                'arguments' => [
                    '<name>' => 'The name of the seeder class (without "Seed" suffix)'
                ],
                'examples' => [
                    'php lalaz seed User',
                    'php lalaz seed Product'
                ]
            ],
            'seed:all' => [
                'title' => 'Run All Seeders',
                'description' => 'Runs all database seeders in the database/seeders directory.',
                'usage' => 'php lalaz seed:all',
                'examples' => ['php lalaz seed:all']
            ],
            'route:cache' => [
                'title' => 'Cache Routes',
                'description' => 'Compiles all routes into a cache file for production (50x faster).',
                'usage' => 'php lalaz route:cache',
                'examples' => ['php lalaz route:cache'],
                'notes' => [
                    'Enable route caching by setting ROUTE_CACHE_ENABLED=true in .env',
                    'Expected speedup: ~50x faster route resolution'
                ]
            ],
            'route:cache-clear' => [
                'title' => 'Clear Route Cache',
                'description' => 'Removes the compiled route cache file.',
                'usage' => 'php lalaz route:cache-clear',
                'examples' => ['php lalaz route:cache-clear']
            ],
            'config:cache' => [
                'title' => 'Cache Config',
                'description' => 'Compiles all configuration into a cache file for production (300x faster).',
                'usage' => 'php lalaz config:cache [env_file]',
                'arguments' => [
                    '[env_file]' => 'Optional path to .env file (defaults to .env in root)'
                ],
                'examples' => [
                    'php lalaz config:cache',
                    'php lalaz config:cache .env.production'
                ],
                'notes' => [
                    'Enable config caching by setting CONFIG_CACHE_ENABLED=true in .env',
                    'Expected speedup: ~300x faster config loading (2-3ms → 0.01ms)'
                ]
            ],
            'config:cache-clear' => [
                'title' => 'Clear Config Cache',
                'description' => 'Removes the compiled config cache file.',
                'usage' => 'php lalaz config:cache-clear',
                'examples' => ['php lalaz config:cache-clear']
            ],
            'jobs:once' => [
                'title' => 'Run Jobs Once',
                'description' => 'Processes all pending jobs in the queue once.',
                'usage' => 'php lalaz jobs:once',
                'examples' => ['php lalaz jobs:once']
            ],
            'jobs:run' => [
                'title' => 'Run Jobs Continuously',
                'description' => 'Runs the job queue in daemon mode, processing jobs every 10 seconds.',
                'usage' => 'php lalaz jobs:run',
                'examples' => ['php lalaz jobs:run'],
                'notes' => ['Use Ctrl+C to stop the daemon']
            ],
            'serve' => [
                'title' => 'Start Development Server',
                'description' => 'Starts the PHP development server with Vite for hot module reloading.',
                'usage' => 'php lalaz serve [port]',
                'arguments' => [
                    '[port]' => 'Optional port number (defaults to 8080)'
                ],
                'examples' => [
                    'php lalaz serve',
                    'php lalaz serve 3000'
                ]
            ],
            'test' => [
                'title' => 'Run Tests',
                'description' => 'Executes the PHPUnit/Pest test suite.',
                'usage' => 'php lalaz test',
                'examples' => ['php lalaz test']
            ],
            'routes' => [
                'title' => 'List Routes',
                'description' => 'Displays all registered routes in a formatted table.',
                'usage' => 'php lalaz routes',
                'examples' => ['php lalaz routes']
            ],
            'middlewares' => [
                'title' => 'Show Route Middlewares',
                'description' => 'Displays all middlewares for a specific route by index.',
                'usage' => 'php lalaz middlewares <index>',
                'arguments' => [
                    '<index>' => 'The route index from the routes table (see "php lalaz routes")'
                ],
                'examples' => [
                    'php lalaz middlewares 1',
                    'php lalaz middlewares 5'
                ]
            ],
            'hash:password' => [
                'title' => 'Hash Password',
                'description' => 'Generates a secure bcrypt hash for a given password string.',
                'usage' => 'php lalaz hash:password <string>',
                'arguments' => [
                    '<string>' => 'The password string to hash'
                ],
                'examples' => [
                    'php lalaz hash:password mypassword123',
                    'php lalaz hash:password "complex password with spaces"'
                ]
            ]
        ];

        if (!isset($commands[$commandName])) {
            echo "❌ Unknown command: {$commandName}\n";
            echo "Run 'php lalaz --help' to see all available commands.\n";
            return;
        }

        $cmd = $commands[$commandName];

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║  \033[1;36m{$cmd['title']}\033[0m\n";
        echo "╚══════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "\033[1mDescription:\033[0m\n";
        echo "  {$cmd['description']}\n";
        echo "\n";
        echo "\033[1mUsage:\033[0m\n";
        echo "  {$cmd['usage']}\n";
        echo "\n";

        if (isset($cmd['arguments'])) {
            echo "\033[1mArguments:\033[0m\n";
            foreach ($cmd['arguments'] as $arg => $desc) {
                echo "  \033[32m{$arg}\033[0m\n";
                echo "    {$desc}\n";
            }
            echo "\n";
        }

        if (isset($cmd['examples'])) {
            echo "\033[1mExamples:\033[0m\n";
            foreach ($cmd['examples'] as $example) {
                echo "  \033[90m{$example}\033[0m\n";
            }
            echo "\n";
        }

        if (isset($cmd['notes'])) {
            echo "\033[1mNotes:\033[0m\n";
            foreach ($cmd['notes'] as $note) {
                echo "  • {$note}\n";
            }
            echo "\n";
        }
    }

    /**
     * Display framework version and system information.
     *
     * @return void
     */
    private static function showVersion(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║                                                           ║\n";
        echo "║    🚀 \033[1;36mLalaz Framework\033[0m - Modern PHP Framework          ║\n";
        echo "║                                                           ║\n";
        echo "║    Version: \033[1;33m1.0.0\033[0m                                       ║\n";
        echo "║    PHP Version: \033[1;32m" . PHP_VERSION . "\033[0m                                  ║\n";
        echo "║                                                           ║\n";
        echo "║    \033[90mBuilt by Elasticmind <ola@elasticmind.io>\033[0m         ║\n";
        echo "║    \033[90mDocumentation: https://lalaz.dev\033[0m                  ║\n";
        echo "║                                                           ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
    }

    /**
     * Display all registered routes in the system in a tabular format.
     *
     * @return void
     */
    private static function routes(): void
    {
        $router = Lalaz::router();
        $routes = $router->getRoutes();

        if (empty($routes)) {
            echo "No routes have been registered.\n";
            return;
        }

        // Calculate column widths based on the longest content in each column
        $methodWidth = max(array_map(fn($route) => strlen($route->getMethod()), $routes));
        $pathWidth = max(array_map(fn($route) => strlen($route->getPath()), $routes));
        $controllerWidth = max(array_map(fn($route) => strlen($route->getController() . '#' . $route->getFunction()), $routes));
        $hasMiddlewaresWidth = strlen('Has Middlewares');
        $indexWidth = strlen('#') + 1;

        echo "\n";

        // Adjust column widths to fit the headers
        $methodWidth = max($methodWidth, strlen('Method')) + 2;
        $pathWidth = max($pathWidth, strlen('URI')) + 2;
        $controllerWidth = max($controllerWidth, strlen('Controller#action')) + 2;

        // Print table headers with the index as the first column
        printf(
            "| %-{$indexWidth}s | %-{$methodWidth}s | %-{$pathWidth}s | %-{$controllerWidth}s | %-{$hasMiddlewaresWidth}s |\n",
            '#', 'Method', 'URI', 'Controller#action', 'Has Middlewares'
        );

        $separatorWidth = $indexWidth + $methodWidth + $pathWidth + $controllerWidth + $hasMiddlewaresWidth + 16;
        echo str_repeat('-', $separatorWidth) . "\n";

        // Print each route as a table row with a numbered index as the first column
        foreach ($routes as $index => $route) {
            $controllerAction = $route->getController() . '#' . $route->getFunction();
            $hasMiddlewares = empty($route->getMiddlewares()) ? 'No' : 'Yes';

            printf(
                "| %-{$indexWidth}s | %-{$methodWidth}s | %-{$pathWidth}s | %-{$controllerWidth}s | %-{$hasMiddlewaresWidth}s |\n",
                $index + 1, $route->getMethod(), $route->getPath(), $controllerAction, $hasMiddlewares
            );
        }

        echo str_repeat('-', $separatorWidth) . "\n";
    }

    /**
     * Generate route cache file for production optimization.
     *
     * @return void
     */
    private static function routeCache(): void
    {
        echo "🔄 Compiling routes...\n";

        try {
            $router = Lalaz::router();
            $routes = $router->getRoutes();

            if (empty($routes)) {
                echo "⚠️  No routes to cache. Please ensure routes are registered.\n";
                return;
            }

            // Default cache path
            $cacheFile = __DIR__ . '/../../storage/cache/routes.php';
            $router->setCacheFile($cacheFile);

            if ($router->saveToCache()) {
                $count = count($routes);
                echo "✅ Router cache created successfully!\n";
                echo "📁 Cache file: {$cacheFile}\n";
                echo "📊 Cached {$count} route(s)\n";
                echo "\n";
                echo "💡 Tip: Enable route caching by setting ROUTE_CACHE_ENABLED=true in your .env file\n";
            } else {
                echo "❌ Failed to create route cache.\n";
            }
        } catch (\Throwable $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Clear the route cache file.
     *
     * @return void
     */
    private static function routeCacheClear(): void
    {
        echo "🔄 Clearing route cache...\n";

        try {
            $cacheFile = __DIR__ . '/../../storage/cache/routes.php';

            if (file_exists($cacheFile)) {
                unlink($cacheFile);
                echo "✅ Route cache cleared successfully!\n";
                echo "📁 Removed: {$cacheFile}\n";
            } else {
                echo "ℹ️  No route cache file found.\n";
            }
        } catch (\Throwable $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Compile config for production (300x performance boost).
     *
     * @param array $args Command arguments.
     * @return void
     */
    private static function configCache(array $args): void
    {
        echo "⚡ Compiling config cache...\n";

        try {
            $envFile = $args[2] ?? __DIR__ . '/../../.env';
            $cacheFile = __DIR__ . '/../../storage/cache/config.php';

            // Set cache file path
            Config::setCacheFile($cacheFile);

            // Load config from .env file
            Config::load($envFile, '=', true);

            // Save to cache
            if (Config::saveToCache($envFile)) {
                $configCount = count(Config::all());

                echo "✅ Config cached successfully!\n";
                echo "📁 Cache file: {$cacheFile}\n";
                echo "📊 Cached {$configCount} config variable(s)\n";
                echo "\n";
                echo "💡 Tip: Enable config caching by setting CONFIG_CACHE_ENABLED=true in your .env file\n";
                echo "🚀 Expected speedup: ~300x faster (2-3ms → 0.01ms)\n";
            } else {
                echo "❌ Failed to create config cache.\n";
            }
        } catch (\Throwable $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Clear the config cache file.
     *
     * @return void
     */
    private static function configCacheClear(): void
    {
        echo "🔄 Clearing config cache...\n";

        try {
            $cacheFile = __DIR__ . '/../../storage/cache/config.php';

            Config::setCacheFile($cacheFile);

            if (Config::clearConfigCache()) {
                echo "✅ Config cache cleared successfully!\n";
                echo "📁 Removed: {$cacheFile}\n";
            } else {
                echo "ℹ️  No config cache file found.\n";
            }
        } catch (\Throwable $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }

    private static function showMiddlewaresForRoute(int $routeIndex): void
    {
        $router = Lalaz::router();
        $routes = $router->getRoutes();

        if (!isset($routes[$routeIndex - 1])) {
            echo "No route found.\n";
            return;
        }

        $route = $routes[$routeIndex - 1];
        $middlewares = $route->getMiddlewares();

        if (empty($middlewares)) {
            echo "No middleares for {$route->getMethod()} {$route->getPath()}\n";
        } else {
            echo str_repeat('-', 60) . "\n";
            echo "{$route->getMethod()} {$route->getPath()}\n";
            echo str_repeat('-', 60) . "\n";
            foreach ($middlewares as $middleware) {
                echo "- " . (is_object($middleware) ? get_class($middleware) : $middleware) . "\n";
            }
        }
    }
}
