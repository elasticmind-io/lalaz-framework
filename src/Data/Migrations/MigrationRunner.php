<?php declare(strict_types=1);

namespace Lalaz\Data\Migrations;

use Lalaz\Lalaz;
use Lalaz\Core\Generators\GeneratorEngine;
use Lalaz\Data\Schema\SchemaBuilder;
use Lalaz\Data\Schema\Blueprint;

/**
 * Class MigrationRunner
 *
 * This class is responsible for managing database migrations. It provides methods to run new migrations,
 * rollback the last migration, reset all migrations, and generate new migration files.
 * It keeps track of executed migrations in a special migrations table within the database.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class MigrationRunner
{
    /** @var string $migrationsTableName The name of the table that stores migration records */
    private static $migrationsTableName = '__migrations';

    /** @var string $migrationsFolder The directory where migration files are stored */
    private static $migrationsFolder = './src/Database/Migrations';

    /**
     * Ensures that the migrations table exists in the database.
     * Uses the Grammar System for database compatibility (MySQL, SQLite, etc.)
     *
     * @return void
     */
    private static function ensureMigrationsTable(): void
    {
        // Check if table already exists
        if (self::migrationsTableExists()) {
            return;
        }

        $tableName = static::$migrationsTableName;

        // Use SchemaBuilder with Grammar System for database compatibility
        SchemaBuilder::create($tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->string('migration', 255);
            $table->integer('batch', false, false, 0);
            $table->timestamp('created_at', false, 'CURRENT_TIMESTAMP');

            // Add index for better performance
            $table->index(['batch']);

            // MySQL-specific options (ignored by SQLite)
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
        });
    }

    /**
     * Checks if the migrations table exists in the database.
     *
     * @return bool True if the table exists, false otherwise.
     */
    private static function migrationsTableExists(): bool
    {
        try {
            $tableName = static::$migrationsTableName;
            Lalaz::db()->query("SELECT 1 FROM $tableName LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retrieves a list of migrations that have already been executed.
     *
     * @return array An array of executed migration filenames.
     */
    private static function getExecutedMigrations(): array
    {
        $tableName = static::$migrationsTableName;

        $executed = [];
        $result = Lalaz::db()->query("SELECT migration FROM $tableName");

        while ($row = $result->fetch()) {
            $executed[] = $row['migration'];
        }

        return $executed;
    }

    /**
     * Retrieves the current batch number (incremented for new migrations).
     *
     * @return int The next batch number.
     */
    private static function getNextBatch(): int
    {
        $tableName = static::$migrationsTableName;

        try {
            $result = Lalaz::db()->query("SELECT MAX(batch) as max_batch FROM $tableName");
            $row = $result->fetch();
            return ($row && $row['max_batch']) ? (int)$row['max_batch'] + 1 : 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Logs a migration as executed by inserting a record into the migrations table.
     * Uses prepared statements to prevent SQL injection.
     *
     * @param string $migrationClass The name of the migration class.
     * @param int    $batch          The batch number.
     * @return void
     */
    private static function logMigration(string $migrationClass, int $batch): void
    {
        $tableName = static::$migrationsTableName;

        $stmt = Lalaz::db()->prepare(
            "INSERT INTO $tableName (migration, batch) VALUES (?, ?)"
        );
        $stmt->execute([$migrationClass, $batch]);
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
        $batch = self::getNextBatch();

        foreach ($migrations as $migrationFile) {
            $migrationClassWithTimestamp = basename($migrationFile, '.php');
            $migrationClass = preg_replace('/^\d{8}_\d{6}_/', '', $migrationClassWithTimestamp);

            if (!in_array($migrationClassWithTimestamp, $executedMigrations)) {
                require_once $migrationFile;
                $migration = new $migrationClass();
                $migration->up();

                self::logMigration($migrationClassWithTimestamp, $batch);
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
        $tableName = static::$migrationsTableName;

        $result = Lalaz::db()->query("SELECT MAX(batch) as max_batch FROM $tableName");
        $row = $result->fetch();
        $lastBatch = $row ? (int)$row['max_batch'] : null;

        if (!$lastBatch) {
            echo "No migrations to rollback.\n";
            return;
        }

        $stmt = Lalaz::db()->prepare(
            "SELECT migration FROM $tableName WHERE batch = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$lastBatch]);
        $lastMigration = $stmt->fetch();

        if ($lastMigration) {
            $migrationClassWithTimestamp = $lastMigration['migration'];
            $migrationClass = preg_replace('/^\d{8}_\d{6}_/', '', $migrationClassWithTimestamp);

            $path = static::$migrationsFolder;
            require_once "$path/{$migrationClassWithTimestamp}.php";

            $migrationInstance = new $migrationClass();
            $migrationInstance->down();

            $deleteStmt = Lalaz::db()->prepare(
                "DELETE FROM $tableName WHERE migration = ?"
            );
            $deleteStmt->execute([$migrationClassWithTimestamp]);

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
        $tableName = static::$migrationsTableName;

        $migrations = Lalaz::db()
            ->query("SELECT migration FROM $tableName ORDER BY batch DESC, id DESC")
            ->fetchAll();

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

            $stmt = Lalaz::db()->prepare(
                "DELETE FROM $tableName WHERE migration = ?"
            );
            $stmt->execute([$migrationClassWithTimestamp]);

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

        $timestamp = date('Ymd_His');
        $filename = "{$directory}/{$timestamp}_{$className}.php";

        // Pass both required parameters to GeneratorEngine
        $engine = new GeneratorEngine('migration.tpl', $filename);
        $engine->setVariables([
            'className' => $className
        ]);

        // generate() saves the file directly (returns void)
        $engine->generate();

        echo "Migration created: {$filename}\n";
    }
}
