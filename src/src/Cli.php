<?php declare(strict_types=1);

namespace Lalaz;

use Lalaz\Generators\GeneratorEngine;
use Lalaz\Data\Migrations\MigrationRunner;

class Cli
{
    public static function command(array $args)
    {
        $command = $args[1];
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

            case 'g:view':
                GeneratorEngine::view($name);
                echo "View {$name} created successfully!\n";
                break;

            case 'migrate':
                MigrationRunner::run();
                break;

            case 'migrate:rollback':
                MigrationRunner::rollback();
                break;

            case 'migrate:reset':
                MigrationRunner::reset();
                break;

            case 'serve':
                $port = $args[2] ?? '8080';
                $phpServerCmd = "php -S localhost:$port";
                $viteCmd = "npm run watch";
                $fullCommand = sprintf('(%s & %s)', $phpServerCmd, $viteCmd);
                passthru($fullCommand);
                break;

            case 'help':
            default:
                echo "Comandos disponíveis:\n";
                echo "  dev    - Executa o servidor de desenvolvimento Vite\n";
                break;
        }

        exit(0);
    }
}
