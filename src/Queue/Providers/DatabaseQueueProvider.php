<?php declare(strict_types=1);

namespace Lalaz\Queue\Providers;

use Lalaz\Queue\Contracts\QueueProviderInterface;
use Lalaz\Data\Database;
use PDO;

/**
 * Class DatabaseQueueProvider
 *
 * This class is a queue provider that stores jobs in a database.
 * It implements the QueueProviderInterface, allowing jobs to be added to
 * the database and processed asynchronously.
 */
class DatabaseQueueProvider implements QueueProviderInterface
{
    /**
     * @var Database The database connection instance.
     */
    protected Database $db;

    /**
     * DatabaseQueueProvider constructor.
     *
     * @param Database $db The database instance to interact with.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;

        // Ensure the jobs table exists when the provider is initialized
        $this->ensureJobsTableExists();
    }

    /**
     * Add a job to the queue (database).
     *
     * This method inserts the job into the database table with the status 'pending'.
     *
     * @param string $jobClass The fully qualified class name of the job.
     * @param array $payload The data required to process the job.
     * @return bool True on success, false on failure.
     */
    public function add(string $jobClass, array $payload = []): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO jobs (task, payload, status) VALUES (:task, :payload, 'pending')"
        );
        return $stmt->execute([
            ':task' => $jobClass,
            ':payload' => json_encode($payload),
        ]);
    }

    /**
     * Process jobs from the queue (database).
     *
     * This method retrieves pending jobs from the database, marks them as processing,
     * executes their handle method, and updates their status based on the result.
     *
     * @return void
     */
    public function process(): void
    {
        // Retrieve a pending job, mark it as 'processing', and lock it for update
        $stmt = $this->db->prepare("SELECT * FROM jobs WHERE status = 'pending' ORDER BY created_at LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job) {
            // Begin a database transaction to ensure job processing integrity
            $this->db->beginTransaction();

            try {
                // Mark the job as 'processing'
                $updateStmt = $this->db->prepare("UPDATE jobs SET status = 'processing' WHERE id = :id");
                $updateStmt->execute([':id' => $job['id']]);

                // Instantiate and run the job
                $jobClass = $job['task'];
                if (class_exists($jobClass)) {
                    $jobInstance = new $jobClass();
                    $payload = json_decode($job['payload'], true);
                    $jobInstance->handle($payload);

                    // Mark the job as 'completed'
                    $completeStmt = $this->db->prepare("UPDATE jobs SET status = 'completed' WHERE id = :id");
                    $completeStmt->execute([':id' => $job['id']]);
                }

                $this->db->commit();
            } catch (\Exception $e) {
                // Rollback transaction and mark the job as 'failed'
                $this->db->rollBack();

                $failStmt = $this->db->prepare("UPDATE jobs SET status = 'failed' WHERE id = :id");
                $failStmt->execute([':id' => $job['id']]);
            }
        }
    }

    /**
     * Ensure that the jobs table exists in the database.
     *
     * This method checks if the jobs table exists and creates it if necessary.
     *
     * @return void
     */
    private function ensureJobsTableExists(): void
    {
        // Check if the jobs table exists
        $tableExistsStmt = $this->db->prepare("SHOW TABLES LIKE 'jobs'");
        $tableExistsStmt->execute();
        $tableExists = $tableExistsStmt->fetch(PDO::FETCH_ASSOC);

        // If the table doesn't exist, create it
        if (!$tableExists) {
            $createTableSql = "
                CREATE TABLE jobs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    task VARCHAR(255) NOT NULL,
                    payload TEXT,
                    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";

            $this->db->exec($createTableSql);
        }
    }
}
