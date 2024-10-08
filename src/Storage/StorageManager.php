<?php declare(strict_types=1);

namespace Lalaz\Storage;

use Lalaz\Core\Config;
use Lalaz\Storage\Contracts\StorageInterface;
use Lalaz\Storage\Adapters\LocalStorageAdapter;
use Lalaz\Storage\Adapters\S3StorageAdapter;

/**
 * Class StorageManager
 *
 * Manages the storage driver based on the configuration and allows for dynamic driver registration.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class StorageManager
{
    /**
     * @var StorageInterface The currently active storage driver.
     */
    protected StorageInterface $storage;

    /**
     * Returns the storage driver instance.
     *
     * @return StorageInterface The initialized storage driver.
     */
    public function getDriver(): StorageInterface
    {
        return $this->storage;
    }

     /**
     * StorageManager constructor.
     *
     * Initializes the storage driver based on the configuration.
     *
     */
    public function __construct()
    {
        $driver = Config::get('STORAGE_DRIVER') ?: 'local';

        switch ($driver) {
            case 's3':
                break;

            case 'local':
            default:
                $path = Config::get('STORAGE_PATH') ?: './public/static';
                $this->storage = new LocalStorageAdapter($path);
                break;
        }
    }
}
