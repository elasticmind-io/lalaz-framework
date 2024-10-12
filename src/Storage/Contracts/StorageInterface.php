<?php declare(strict_types=1);

namespace Lalaz\Storage\Contracts;

/**
 * Interface StorageInterface
 *
 * Provides a contract for file storage operations, including uploading, downloading,
 * deleting, and generating public URLs for files.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
interface StorageInterface
{
    /**
     * Uploads a file to the storage backend.
     *
     * @param string $path The destination path in storage.
     * @param string $localPath The local file path to be uploaded.
     * @return string The public URL of the uploaded file.
     */
    public function upload(string $path, string $localPath): string;

    /**
     * Downloads a file from storage.
     *
     * @param string $path The path in storage.
     * @return string The local file path of the downloaded content.
     */
    public function download(string $path): string;

    /**
     * Deletes a file from storage.
     *
     * @param string $path The path in storage.
     * @return bool True if file deleted successfully, otherwise false.
     */
    public function delete(string $path): bool;

    /**
     * Returns the public URL for a file in storage.
     *
     * @param string $path The path in storage.
     * @return string The public URL.
     */
    public function getPublicUrl(string $path): string;
}
