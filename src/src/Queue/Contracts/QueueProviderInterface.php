<?php declare(strict_types=1);

namespace Lalaz\Queue\Contracts;

/**
 * Interface QueueProviderInterface
 *
 * This interface defines the contract for queue providers. Each provider must
 * implement methods for adding jobs to the queue and processing the jobs in the queue.
 * This abstraction allows for different queue backends (e.g., Database, File System).
 */
interface QueueProviderInterface
{
    /**
     * Add a job to the queue.
     *
     * This method takes the class name of the job and the payload data,
     * then adds it to the queue for later processing.
     *
     * @param string $jobClass The fully qualified class name of the job.
     * @param array $payload The data required to process the job.
     * @return bool True on success, false on failure.
     */
    public function add(string $jobClass, array $payload = []): bool;

    /**
     * Process jobs in the queue.
     *
     * This method retrieves and processes jobs from the queue, executing their
     * handle method with the provided payload data.
     *
     * @return void
     */
    public function process(): void;
}
