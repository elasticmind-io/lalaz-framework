<?php declare(strict_types=1);

namespace Lalaz\Queue;

use Lalaz\Core\Config;
use Throwable;

/**
 * Class Jobs
 *
 * This class acts as an entry point to execute queued jobs within the application.
 * It provides a method to run all pending jobs by utilizing the JobRunner class.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Jobs
{
        /**
     * Executes all pending jobs in the queue.
     *
     * Creates an instance of JobRunner and calls its `run()` method
     * to process all the jobs. Optionally, logs the start and end of the execution.
     * Catches and handles any exceptions that may occur during the job processing.
     *
     * @return void
     */
    public static function run(): void
    {
        try {
            Config::load('src/App/.env');
            echo "Starting job execution...\n";
            $jobRunner = new JobRunner();
            $jobRunner->run();
            echo "Job execution finished.\n";
        } catch (Throwable $e) {
            echo "An error occurred during job execution: " . $e->getMessage() . "\n";
        }
    }
}
