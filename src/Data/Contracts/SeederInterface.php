<?php declare(strict_types=1);

namespace Lalaz\Data\Contracts;

/**
 * Interface SeederInterface
 *
 * All seeders should implement this interface.
 *
 * @package  elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link     https://lalaz.dev
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
