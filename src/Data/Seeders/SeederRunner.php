<?php declare(strict_types=1);

namespace Lalaz\Data\Seeders;

use Lalaz\Lalaz;
use Lalaz\Data\Database;

class SeederRunner
{
    /**
     * The database connection instance.
     *
     * @var Database
     */
    protected Database $db;

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
        static $baseDir = './src/Db/Seeders/';

        $seederClassName = ucwords($seederClass);
        $filePath = $baseDir . str_replace('/', DIRECTORY_SEPARATOR, $seederClassName) . '.php';

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
     * Execute all seeders provided.
     *
     * @param array $seeders An array of fully qualified seeder class names.
     * @return void
     */
    public function runSeeders(array $seeders): void
    {
        foreach ($seeders as $seederClass) {
            $this->runSeeder($seederClass);
        }
    }
}
