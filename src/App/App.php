<?php declare(strict_types=1);

namespace App;

use Lalaz\Lalaz;
use Lalaz\Logging\Logger;
use Lalaz\Logging\LogToConsole;

/**
 * Class App
 *
 * This class serves as the entry point for the application. It initializes the application by setting up the logger,
 * creating an instance of the Lalaz framework, loading the routes, and running the application.
 *
 * @package App
 */
class App
{
    /**
     * Starts the application.
     *
     * This static method initializes the logger, creates an instance of the Lalaz framework with the logger,
     * includes the web routes, and runs the application.
     *
     * @return void
     */
    public static function start(): void
    {
        // Initialize the logger
        $logger = Logger::create()->writeTo(new LogToConsole());

        // Create an instance of the Lalaz application
        $app = new Lalaz(__DIR__, $logger);

        // Include the web routes
        require 'Routes/web.php';

        // Run the application
        $app->run();
    }
}
