<?php declare(strict_types=1);

namespace Lalaz\Queue\Providers;

use Lalaz\Queue\Contracts\QueueProviderInterface;

/**
 * Class FileQueueProvider
 *
 * This class is a queue provider that stores jobs in the file system.
 * Each job is stored as a JSON file in a specified directory. The jobs
 * are processed asynchronously by reading and executing these files.
 */
class FileQueueProvider implements QueueProviderInterface
{
    /**
     * @var string The directory where the jobs are stored.
     */
    protected string $queueDir;

    /**
     * FileQueueProvider constructor.
     *
     * @param string|null $queueDir The directory to store the job files.
     * If null, defaults to '/queue' in the project root.
     */
    public function __construct(string $queueDir = null)
    {
        // Default directory for storing job files
        $this->queueDir = $queueDir ?: __DIR__ . '/../../../../queue';

        // Ensure the queue directory exists
        $this->ensureQueueDirectoryExists();
    }

    /**
     * Add a job to the queue (file system).
     *
     * This method stores the job as a JSON file in the queue directory.
     *
     * @param string $jobClass The fully qualified class name of the job.
     * @param array $payload The data required to process the job.
     * @return bool True on success, false on failure.
     */
    public function add(string $jobClass, array $payload = []): bool
    {
        $fileName = uniqid('job_', true) . '.json';
        $jobData = json_encode([
            'task' => $jobClass,
            'payload' => $payload,
        ]);

        return file_put_contents($this->queueDir . '/' . $fileName, $jobData) !== false;
    }

    /**
     * Process jobs from the queue (file system).
     *
     * This method reads job files from the queue directory, executes the job,
     * and then moves the processed file to a subdirectory 'processed'.
     *
     * @return void
     */
    public function process(): void
    {
        $jobFiles = glob($this->queueDir . '/*.json');

        foreach ($jobFiles as $jobFile) {
            // Read the job file
            $jobData = json_decode(file_get_contents($jobFile), true);

            if ($jobData && isset($jobData['task'])) {
                $jobClass = $jobData['task'];

                if (class_exists($jobClass)) {
                    // Instantiate the job and run it
                    $jobInstance = new $jobClass();
                    $payload = $jobData['payload'] ?? [];
                    $jobInstance->handle($payload);

                    // Move the processed job file to the 'processed' subdirectory
                    $this->moveToProcessed($jobFile);
                }
            }
        }
    }

    /**
     * Ensure the queue directory exists.
     *
     * This method creates the queue directory if it does not exist.
     *
     * @return void
     */
    private function ensureQueueDirectoryExists(): void
    {
        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }

        // Ensure the 'processed' subdirectory also exists
        if (!is_dir($this->queueDir . '/processed')) {
            mkdir($this->queueDir . '/processed', 0755, true);
        }
    }

    /**
     * Move a processed job file to the 'processed' subdirectory.
     *
     * @param string $jobFile The full path of the job file to move.
     * @return void
     */
    private function moveToProcessed(string $jobFile): void
    {
        $processedDir = $this->queueDir . '/processed';
        $fileName = basename($jobFile);
        rename($jobFile, $processedDir . '/' . $fileName);
    }
}
