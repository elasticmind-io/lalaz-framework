<?php declare(strict_types=1);

namespace App;

use Lalaz\Lalaz;
use Lalaz\Logging\Logger;
use Lalaz\Logging\LogToConsole;

class App
{
    public static function start(): void
    {
        $logger = Logger::create()
            ->writeTo(new LogToConsole());

        Lalaz::initialize(__DIR__, $logger)
            ->run();
    }
}
