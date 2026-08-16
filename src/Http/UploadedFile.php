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
     * @param array $allowedExtensions The allowed file extensions (e.g., ['jpg', 'png', 'pdf']).
     * @param int|null $maxSize The maximum file size in bytes.
     * @param bool $validateMagicBytes Whether to validate actual file type using magic bytes.
     * @return bool True if valid, otherwise throws an exception.
     * @throws \Exception If file validation fails.
     */
    public function validate(
        array $allowedTypes = [],
        array $allowedExtensions = [],
        ?int $maxSize = null,
        bool $validateMagicBytes = true
    ): bool {
        // Validate file upload error
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload failed with error code: ' . $this->file['error']);
        }

        // Validate file size
        if ($maxSize && $this->file['size'] > $maxSize) {
            throw new \Exception('File size exceeds limit.');
        }

        // Validate file extension
        if (!empty($allowedExtensions)) {
            $ext = strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, array_map('strtolower', $allowedExtensions))) {
                throw new \Exception('Invalid file extension. Allowed: ' . implode(', ', $allowedExtensions));
            }
        }

        // Validate declared MIME type
        if (!empty($allowedTypes) && !in_array($this->file['type'], $allowedTypes)) {
            throw new \Exception('Invalid file type.');
        }

        // Validate actual file type using magic bytes (prevents spoofing)
        if ($validateMagicBytes && !empty($allowedTypes)) {
            if (!file_exists($this->file['tmp_name'])) {
                throw new \Exception('Uploaded file not found.');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                throw new \Exception('Unable to detect file type.');
            }

            $realMimeType = finfo_file($finfo, $this->file['tmp_name']);
            finfo_close($finfo);

            if ($realMimeType === false) {
                throw new \Exception('Unable to detect file type.');
            }

            if (!in_array($realMimeType, $allowedTypes)) {
                throw new \Exception(
                    'File content does not match declared type. ' .
                    'Detected: ' . $realMimeType . ', Expected one of: ' . implode(', ', $allowedTypes)
                );
            }
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
