<?php declare(strict_types=1);

namespace Lalaz\Storage\Adapters;

use Lalaz\IO\Directory;
use Lalaz\Storage\Contracts\StorageInterface;

/**
 * Class LocalStorageAdapter
 *
 * Handles file storage operations on the local filesystem.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class LocalStorageAdapter implements StorageInterface
{
    /**
     * @var string $basePath The base path for file storage.
     */
    protected string $basePath;

    /**
     * LocalStorageAdapter constructor.
     *
     * @param string $basePath The base path for storing files.
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Uploads a file to the local storage.
     *
     * @param string $path The relative path to store the file.
     * @param string $localPath The local path of the file to upload.
     * @return string The public URL of the uploaded file.
     */
    public function upload(string $path, string $localPath): string
    {
        $uniqueFileName = self::generateUniqueFileName($path);
        $destination = $this->basePath . ltrim($uniqueFileName, '/');
        Directory::ensureDirectoryExists($destination);
        copy($localPath, $destination);
        return $this->getPublicUrl($uniqueFileName);
    }

    /**
     * Downloads a file from the local storage.
     *
     * @param string $path The relative path of the file to download.
     * @return string The full path to the file in the local storage.
     */
    public function download(string $path): string
    {
        return '';
    }

    /**
     * Deletes a file from the local storage.
     *
     * @param string $path The relative path of the file to delete.
     * @return bool True if the file was deleted successfully.
     */
    public function delete(string $path): bool
    {
        return unlink($this->basePath . ltrim($path, '/'));
    }

    /**
     * Generates a public URL for accessing the file.
     *
     * @param string $path The relative path of the file.
     * @return string The public URL of the file.
     */
    public function getPublicUrl(string $path): string
    {
        return '/public/static/' . ltrim($path, '/');
    }

    /**
     * Generates a unique file name to prevent overwriting existing files.
     *
     * @param string $originalName The original name of the file.
     * @return string The unique file name generated.
     */
    private static function generateUniqueFileName(string $originalName): string
    {
        $pathInfo = pathinfo($originalName);

        $subPath = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.'
            ? $pathInfo['dirname'] . DIRECTORY_SEPARATOR
            : '';

        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $uniqueName = uniqid('', true);

        return $subPath . $uniqueName . $extension;
    }
}
