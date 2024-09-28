<?php declare(strict_types=1);

namespace Lalaz\Data\Migrations;

use Lalaz\Lalaz;
use Lalaz\Generators\GeneratorEngine;

/**
 * Class MigrationRunner
 *
 * This class is responsible for managing database migrations. It provides methods to run new migrations,
 * rollback the last migration, reset all migrations, and generate new migration files.
 * It keeps track of executed migrations in a special migrations table within the database.
 *
 * @package Lalaz\Data\Migrations
 */
class MigrationRunner
{
    /** @var string $migrationsTableName The name of the table that stores migration records */
    private static $migrationsTableName = '__migrations';

    /** @var string $migrationsFolder The directory where migration files are stored */
    private static $migrationsFolder = './src/Db/Migrations';

    /**
     * Ensures that the migrations table exists in the database.
     *
     * @return void
     */
    private static function ensureMigrationsTable(): void
    {
        $tablename = static::$migrationsTableName;

        Lalaz::db()->exec("CREATE TABLE IF NOT EXISTS $tablename (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            batch INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=INNODB;");
    }

    /**
     * Retrieves a list of migrations that have already been executed.
     *
     * @return array An array of executed migration filenames.
     */
    private static function getExecutedMigrations(): array
    {
        $tablename = static::$migrationsTableName;
        $executed = [];
        $result = Lalaz::db()->query("SELECT migration FROM $tablename");

        while ($row = $result->fetch()) {
            $executed[] = $row['migration'];
        }

        return $executed;
    }

    /**
     * Logs a migration as executed by inserting a record into the migrations table.
     *
     * @param string $migrationClass The name of the migration class.
     * @return void
     */
    private static function logMigration(string $migrationClass): void
    {
        $tablename = static::$migrationsTableName;
        Lalaz::db()->exec("INSERT INTO $tablename (migration, batch) VALUES ('$migrationClass', 1)");
    }

    /**
     * Runs all pending migrations that have not yet been executed.
     *
     * @return void
     */
    public static function run(): void
    {
        self::ensureMigrationsTable();

        $path = static::$migrationsFolder;
        $migrations = glob("$path/*.php");
        $executedMigrations = self::getExecutedMigrations();

        foreach ($migrations as $migrationFile) {
            $migrationClassWithTimestamp = basename($migrationFile, '.php');
            $migrationClass = preg_replace('/^\d{8}_\d{6}_/', '', $migrationClassWithTimestamp);

            if (!in_array($migrationClassWithTimestamp, $executedMigrations)) {
                require_once $migrationFile;
                $migration = new $migrationClass();
                $migration->up();

                self::logMigration($migrationClassWithTimestamp);
                echo "Migrated: $migrationClass\n";
            }
        }
    }

    /**
     * Rolls back the last executed migration.
     *
     * @return void
     */
    public static function rollback(): void
    {
        $tablename = static::$migrationsTableName;
        $lastBatch = Lalaz::db()->query("SELECT MAX(batch) FROM $tablename")->fetchColumn();

        if (!$lastBatch) {
            echo "No migrations to rollback.\n";
            return;
        }

        $lastMigration = Lalaz::db()->query("SELECT migration FROM $tablename WHERE batch = $lastBatch ORDER BY id DESC LIMIT 1")->fetch();

        if ($lastMigration) {
            $migrationClassWithTimestamp = $lastMigration['migration'];
            $migrationClass = preg_replace('/^\d{8}_\d{6}_/', '', $migrationClassWithTimestamp);

            $path = static::$migrationsFolder;
            require_once "$path/{$migrationClassWithTimestamp}.php";

            $migrationInstance = new $migrationClass();
            $migrationInstance->down();

            Lalaz::db()->exec("DELETE FROM $tablename WHERE migration = '$migrationClassWithTimestamp'");

            echo "Rolled back: $migrationClassWithTimestamp\n";
        } else {
            echo "No migration to rollback.\n";
        }
    }

    /**
     * Resets all migrations by rolling back every executed migration.
     *
     * @return void
     */
    public static function reset(): void
    {
        $tablename = static::$migrationsTableName;
        $migrations = Lalaz::db()->query("SELECT migration FROM $tablename ORDER BY batch DESC, id DESC")->fetchAll();

        if (empty($migrations)) {
            echo "No migrations to reset.\n";
            return;
        }

        foreach ($migrations as $migration) {
            $migrationClassWithTimestamp = $migration['migration'];
            $migrationClass = preg_replace('/^\d{8}_\d{6}_/', '', $migrationClassWithTimestamp);

            $path = static::$migrationsFolder;
            require_once "$path/{$migrationClassWithTimestamp}.php";

            $migrationInstance = new $migrationClass();
            $migrationInstance->down();

            Lalaz::db()->exec("DELETE FROM $tablename WHERE migration = '$migrationClassWithTimestamp'");

            echo "Rolled back: $migrationClassWithTimestamp\n";
        }

        echo "All migrations have been reset.\n";
    }

    /**
     * Generates a new migration file with the given name.
     *
     * @param string $migrationName The name of the migration to create.
     * @return void
     */
    public static function generate(string $migrationName): void
    {
        $className = ucfirst($migrationName);
        $directory = static::$migrationsFolder;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            echo "Directory '$directory' created.\n";
        }

        $engine = new GeneratorEngine('migration.tpl');
        $engine->setVariables([
            'className' => $className
        ]);

        $migrationContent = $engine->generate();
        $timestamp = date('Ymd_His');
        $filename = "{$directory}/{$timestamp}_{$className}.php";

        file_put_contents($filename, $migrationContent);

        echo "Migration created: {$filename}\n";
    }
}
