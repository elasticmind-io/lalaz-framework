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

            case 'g:entity':
                GeneratorEngine::entity($name);
                echo "Entity {$name} created successfully!\n";
                break;

            case 'g:presenter':
                GeneratorEngine::presenter($name);
                echo "Presenter {$name} created successfully!\n";
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

            case 'middlewares':
                static::showMiddlewaresForRoute((int)$name);
                break;

            case 'serve':
                $port = $args[2] ?? '8080';
                $phpServerCmd = "php -S localhost:$port";
                $viteCmd = "npm run watch";
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
        echo "Available commands:\n";
        echo "  g:controller [name]     - Generate a controller\n";
        echo "  g:middleware [name]     - Generate a middleware\n";
        echo "  g:model [name]          - Generate a model\n";
        echo "  g:entity [name]         - Generate an entity\n";
        echo "  g:presenter [name]      - Generate a presenter\n";
        echo "  g:migration [name]      - Generate a migration\n";
        echo "  g:seeder [name]         - Generate a seeder\n";
        echo "  g:view [name]           - Generate a view\n";
        echo "  migrate                 - Run all migrations\n";
        echo "  migrate:rollback        - Rollback the last migration batch\n";
        echo "  migrate:reset           - Reset all migrations\n";
        echo "  seed [name]             - Run a database seeder\n";
        echo "  jobs:once               - Run the job queue once\n";
        echo "  jobs:run                - Run the job queue continuously\n";
        echo "  routes                  - Display all registered routes\n";
        echo "  serve [port]            - Serve the application with Vite and PHP\n";
        echo "  test                    - Run PHPUnit tests\n";
        echo "  help                    - Display this help message\n";
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
