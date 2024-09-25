<?php declare(strict_types=1);

namespace Lalaz\Queue;

use Lalaz\Queue\Contracts\JobInterface;

/**
 * Class Job
 *
 * This abstract class provides the base functionality for all jobs.
 * It includes methods for executing the job immediately (synchronously)
 * or queuing the job for later execution (asynchronously).
 */
abstract class Job implements JobInterface
{
    /**
     * Execute the job immediately.
     *
     * This method calls the handle method directly to perform the job
     * without adding it to a queue.
     *
     * @param array $payload The data required to process the job.
     * @return void
     */
    public function performNow(array $payload): void
    {
        $this->handle($payload);
    }

    /**
     * Queue the job for later execution.
     *
     * This method enqueues the job to be processed asynchronously. It delegates
     * the queuing logic to the QueueManager, which handles the underlying queue system.
     *
     * @param array $payload The data required to process the job.
     * @return void
     */
    public function performLater(array $payload): void
    {
        $queueManager = new QueueManager();
        $queueManager->addJob(static::class, $payload);
    }
}
