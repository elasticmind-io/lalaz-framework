#!/usr/bin/env php
<?php

define('LALAZ_START', microtime(true));

// Register the Composer autoloader...
require __DIR__ . '/vendor/autoload.php';

use Lalaz\Generators\GeneratorEngine;
use Lalaz\Data\Migrations\MigrationRunner;

$command = $argv[1];
$name = $argv[2] ?? '';

$lname = strtolower($name);

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

    case 'help':
        break;
}

exit(0);
