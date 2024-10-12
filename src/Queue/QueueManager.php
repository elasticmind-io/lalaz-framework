<?php declare(strict_types=1);

namespace Lalaz\Queue;

use Lalaz\Lalaz;
use Lalaz\Core\Config;
use Lalaz\Data\Database;
use Lalaz\Queue\Contracts\QueueProviderInterface;
use Lalaz\Queue\Providers\DatabaseQueueProvider;
use Lalaz\Queue\Providers\FileQueueProvider;

/**
 * Class QueueManager
 *
 * This class manages the job queue system. It abstracts the underlying provider,
 * allowing for different queue backends (e.g., Database, File System).
 * The QueueManager handles adding jobs to the queue and processing them by
 * delegating to the appropriate queue provider.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class QueueManager
{
    /**
     * @var QueueProviderInterface The selected provider for handling queue operations.
     */
    protected QueueProviderInterface $provider;

    /**
     * QueueManager constructor.
     *
     * This constructor initializes the queue provider based on the system configuration.
     * The provider could be a database, file system, or any other supported backend.
     */
    public function __construct()
    {
        $providerType = Config::get('QUEUE_PROVIDER') ?: 'database';

        switch ($providerType) {
            case 'file':
                $this->provider = new FileQueueProvider();
                break;

            case 'database':
            default:
                $this->provider = new DatabaseQueueProvider(Lalaz::db());
        }
    }

    /**
     * Add a job to the queue.
     *
     * This method adds a job to the queue using the selected queue provider.
     *
     * @param string $jobClass The fully qualified class name of the job.
     * @param array $payload The data required to process the job.
     * @return bool True on success, false on failure.
     */
    public function addJob(string $jobClass, array $payload = []): bool
    {
        return $this->provider->add($jobClass, $payload);
    }

    /**
     * Process the jobs in the queue.
     *
     * This method processes the queued jobs using the selected provider.
     * It retrieves jobs from the queue and executes them.
     *
     * @return void
     */
    public function processJobs(): void
    {
        $this->provider->process();
    }
}
