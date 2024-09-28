<?php declare(strict_types=1);

namespace Lalaz\Queue;

/**
 * Interface JobInterface
 *
 * This interface defines the contract for all jobs that will be processed
 * either immediately or queued for asynchronous execution. Each job must
 * implement the handle method, which contains the logic to process the job.
 */
interface JobInterface
{
    /**
     * Handle the execution of the job.
     *
     * This method will contain the main logic for processing the job.
     *
     * @param array $payload The data required to process the job.
     * @return void
     */
    public function handle(array $payload): void;
}
