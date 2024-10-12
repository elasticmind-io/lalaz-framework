<?php declare(strict_types=1);

namespace Lalaz\Http;

use Lalaz\Storage\StorageManager;

/**
 * Class UploadedFile
 *
 * This class encapsulates the functionality of handling uploaded files in HTTP requests.
 * It provides methods for validating the uploaded file's properties (type and size),
 * as well as storing the file using a specified storage driver.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class UploadedFile
{
    protected array $file;

    public function __construct(array $file)
    {
        $this->file = $file;
    }

    /**
     * Validates the uploaded file.
     *
     * @param array $allowedTypes The allowed MIME types.
     * @param int|null $maxSize The maximum file size in bytes.
     * @return bool True if valid, otherwise throws an exception.
     * @throws \Exception If file validation fails.
     */
    public function validate(array $allowedTypes = [], ?int $maxSize = null): bool
    {
        if (!empty($allowedTypes) && !in_array($this->file['type'], $allowedTypes)) {
            throw new \Exception('Invalid file type.');
        }

        if ($maxSize && $this->file['size'] > $maxSize) {
            throw new \Exception('File size exceeds limit.');
        }

        return true;
    }

    /**
     * Store the file to the given storage path.
     *
     * @param string $path The destination path in storage.
     * @return string The public URL or file path.
     */
    public function store(string $path): string
    {
        $storage = new StorageManager();
        return $storage->getDriver()->upload($path, $this->file['tmp_name']);
    }

    /**
     * Returns the original file name.
     *
     * @return string The original file name.
     */
    public function getClientOriginalName(): string
    {
        return $this->file['name'];
    }
}
