<?php declare(strict_types=1);

namespace Lalaz\Queue\Providers;

use Lalaz\Queue\Contracts\QueueProviderInterface;

/**
 * Class InMemoryQueueProvider
 *
 * A queue provider that stores jobs in memory only. Useful for testing or DB-less environments.
 *
 * @package elasticmind\lalaz-framework
 */
class InMemoryQueueProvider implements QueueProviderInterface
{
    /**
     * @var array The list of jobs stored in memory.
     */
    protected array $queue = [];

    /**
     * Add a job to the in-memory queue.
     *
     * @param string $jobClass The fully qualified class name of the job.
     * @param array $payload The data required to process the job.
     * @return bool True on success.
     */
    public function add(string $jobClass, array $payload = []): bool
    {
        $this->queue[] = [
            'task'    => $jobClass,
            'payload' => $payload,
            'status'  => 'pending',
        ];

        return true;
    }

    /**
     * Process the first job in the queue.
     *
     * @return void
     */
    public function process(): void
    {
        foreach ($this->queue as $key => $job) {
            if ($job['status'] !== 'pending') {
                continue;
            }

            $this->queue[$key]['status'] = 'processing';

            try {
                $jobClass = $job['task'];

                if (class_exists($jobClass)) {
                    $instance = new $jobClass();
                    $instance->handle($job['payload']);

                    $this->queue[$key]['status'] = 'completed';
                } else {
                    $this->queue[$key]['status'] = 'failed';
                }
            } catch (\Throwable $e) {
                $this->queue[$key]['status'] = 'failed';
            }

            // Processa apenas um por chamada (como o DBProvider)
            break;
        }
    }

    /**
     * Returns the current queue for inspection (mainly for testing).
     *
     * @return array
     */
    public function all(): array
    {
        return $this->queue;
    }
}
