<?php declare(strict_types=1);

namespace Lalaz\Data\Seeders;

use Lalaz\Data\Database;

/**
 * Class Seeder
 *
 * Base class for creating database seeders.
 *
 * @package  elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link     https://lalaz.dev
 */
abstract class Seeder implements SeederInterface
{
    /**
     * The database connection instance.
     *
     * @var Database
     */
    protected Database $db;

    /**
     * Seeder constructor.
     *
     * @param Database $db The database connection to be used.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Run the seed. This method should be implemented by each seeder.
     *
     * @return void
     */
    abstract public function run(): void;
}
