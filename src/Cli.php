<?php declare(strict_types=1);

namespace Lalaz;

use Lalaz\Lalaz;
use Lalaz\Generators\GeneratorEngine;
use Lalaz\Data\Migrations\MigrationRunner;
use Lalaz\Data\Seeders\SeederRunner;
use Lalaz\Queue\Jobs;

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
 * @namespace Lalaz
 * @package  elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link     https://lalaz.dev
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
        // Extract the main command and name argument
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

            case 'serve':
                $port = $args[2] ?? '8080';
                $phpServerCmd = "php -S localhost:$port";
                $viteCmd = "npm run watch";
                $fullCommand = sprintf('(%s & %s)', $phpServerCmd, $viteCmd);
                passthru($fullCommand);
                echo "Server running on port {$port}\n";
                break;

            case 'test':
                passthru('vendor/bin/phpunit');
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
        echo "  serve [port]            - Serve the application with Vite and PHP\n";
        echo "  test                    - Run PHPUnit tests\n";
        echo "  help                    - Display this help message\n";
    }
}
