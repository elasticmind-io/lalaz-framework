<?php declare(strict_types=1);

namespace Lalaz\Queue;

/**
 * Class JobRunner
 *
 * This class is responsible for running jobs from the queue. It acts as an interface
 * for manually triggering the job processing, either via a cron job or a direct method call.
 * The JobRunner uses the QueueManager to process all jobs in the queue.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class JobRunner
{
    /**
     * Run all pending jobs in the queue.
     *
     * This method processes all jobs in the queue by calling the process method
     * of the QueueManager, which delegates the job processing to the configured provider.
     *
     * @return void
     */
    public function run(): void
    {
        $queueManager = new QueueManager();
        $queueManager->processJobs();
    }
}
