<?php declare(strict_types=1);

namespace Lalaz\Storage;

use Lalaz\Storage\Contracts\StorageInterface;
use Lalaz\Storage\Adapters\LocalStorageAdapter;
use Lalaz\Storage\Adapters\S3StorageAdapter;

/**
 * Class Storage
 *
 * Provides access to different storage drivers through a unified interface.
 * Uses StorageManager to determine which driver to initialize and provides
 * caching to avoid multiple initializations of the same driver.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Storage
{
    /**
     * Returns an instance of the specified storage driver.
     *
     * @param string|null $driverName The name of the storage driver to use (optional).
     *                                If null, uses the default driver from StorageManager.
     * @return StorageInterface The initialized storage driver.
     */
    public static function driver(): StorageInterface
    {
        $manager = new StorageManager();
        return new $manager->getDriver();
    }
}
