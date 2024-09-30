<?php declare(strict_types=1);

namespace Lalaz\Data\Seeders;

/**
 * Interface SeederInterface
 *
 * All seeders should implement this interface.
 */
interface SeederInterface
{
    /**
     * Run the database seed.
     *
     * @return void
     */
    public function run(): void;
}
