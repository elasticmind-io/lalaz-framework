<?php declare(strict_types=1);

namespace Lalaz\Data\Seeders;

use Lalaz\Lalaz;
use Lalaz\Data\Database;

/**
 * Class SeederRunner
 *
 * This class handles the execution of database seeders. It scans the seeders
 * directory, loads the seed classes, and executes them to populate the database.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class SeederRunner
{
    /**
     * The database connection instance.
     *
     * @var Database
     */
    protected Database $db;

    /**
     * The base directory where seeders are located.
     *
     * @var string
     */
    protected string $baseDir = './src/Db/Seeders/';

    /**
     * SeederRunner constructor.
     *
     * @param Database $db The database connection instance.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Execute the given seeder class.
     *
     * @param string $seederClass The fully qualified name of the seeder class.
     * @return void
     */
    public function runSeeder(string $seederClass): void
    {
        $seederClassName = ucwords($seederClass);
        $filePath = $this->baseDir . str_replace('/', DIRECTORY_SEPARATOR, $seederClassName) . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;

            if (class_exists($seederClassName)) {
                /** @var SeederInterface $seeder */
                $seeder = new $seederClassName($this->db);
                $seeder->run();
                echo "Seeder {$seederClassName} executed successfully.\n";
            } else {
                echo "Seeder class {$seederClassName} not found in file.\n";
            }
        } else {
            echo "Seeder file {$filePath} not found.\n";
        }
    }

    /**
     * Execute all seeders in the base directory.
     *
     * This method will scan the seeder directory and execute all seeder classes found.
     *
     * @return void
     */
    public function runSeeders(): void
    {
        $seederFiles = $this->getSeederFiles();

        foreach ($seederFiles as $filePath) {
            // Extract the seeder class name from the file path
            $seederClassName = $this->extractSeederClassName($filePath);
            $this->runSeeder($seederClassName);
        }
    }

    /**
     * Get all PHP seeder files in the base directory.
     *
     * Scans the directory recursively to find all PHP files.
     *
     * @return array An array of file paths for seeder classes.
     */
    protected function getSeederFiles(): array
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->baseDir));
        $files = [];

        foreach ($rii as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Extract the seeder class name from its file path.
     *
     * Converts a file path into the corresponding seeder class name.
     *
     * @param string $filePath The path to the seeder file.
     * @return string The fully qualified seeder class name.
     */
    protected function extractSeederClassName(string $filePath): string
    {
        // Remove base directory and extension
        $relativePath = str_replace($this->baseDir, '', $filePath);
        $className = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
        return preg_replace('/\.php$/', '', $className);
    }
}
