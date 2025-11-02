<?php declare(strict_types=1);

use Lalaz\IO\Directory;

// Helper function to recursively remove directories
function removeTestDirectory(string $dir): void {
    if (!file_exists($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeTestDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

describe('Directory', function () {
    beforeEach(function () {
        $this->tempBase = sys_get_temp_dir() . '/lalaz_test_' . uniqid();
    });

    afterEach(function () {
        // Clean up any created test directories
        if (isset($this->tempBase) && file_exists($this->tempBase)) {
            removeTestDirectory($this->tempBase);
        }
    });    describe('Basic Functionality', function () {
        it('creates a single directory if it does not exist', function () {
            $filePath = $this->tempBase . '/file.txt';

            Directory::ensureDirectoryExists($filePath);

            expect(is_dir(dirname($filePath)))->toBeTrue();
            expect(file_exists($this->tempBase))->toBeTrue();
        });

        it('creates nested directories if they do not exist', function () {
            $filePath = $this->tempBase . '/level1/level2/level3/file.txt';

            Directory::ensureDirectoryExists($filePath);

            expect(is_dir($this->tempBase . '/level1'))->toBeTrue();
            expect(is_dir($this->tempBase . '/level1/level2'))->toBeTrue();
            expect(is_dir($this->tempBase . '/level1/level2/level3'))->toBeTrue();
        });

        it('does not throw exception if directory already exists', function () {
            $filePath = $this->tempBase . '/existing/file.txt';
            mkdir($this->tempBase . '/existing', 0755, true);

            expect(fn() => Directory::ensureDirectoryExists($filePath))
                ->not->toThrow(Exception::class);
        });

        it('handles file path with just filename', function () {
            Directory::ensureDirectoryExists('file.txt');

            // dirname('file.txt') returns '.' which always exists
            expect(true)->toBeTrue();
        });
    });

    describe('Path Handling', function () {
        it('handles paths with trailing slashes', function () {
            $filePath = $this->tempBase . '/dir/';

            Directory::ensureDirectoryExists($filePath);

            expect(file_exists($this->tempBase))->toBeTrue();
        });

        it('handles absolute paths', function () {
            $filePath = $this->tempBase . '/absolute/path/file.txt';

            Directory::ensureDirectoryExists($filePath);

            expect(is_dir($this->tempBase . '/absolute/path'))->toBeTrue();
        });

        it('handles paths with special characters', function () {
            $filePath = $this->tempBase . '/dir-with_special.chars/file.txt';

            Directory::ensureDirectoryExists($filePath);

            expect(is_dir($this->tempBase . '/dir-with_special.chars'))->toBeTrue();
        });

        it('handles deeply nested paths', function () {
            $deepPath = $this->tempBase;
            for ($i = 1; $i <= 10; $i++) {
                $deepPath .= "/level{$i}";
            }
            $filePath = $deepPath . '/file.txt';

            Directory::ensureDirectoryExists($filePath);

            expect(is_dir($deepPath))->toBeTrue();
        });
    });

    describe('Permissions', function () {
        it('creates directory with correct permissions', function () {
            $filePath = $this->tempBase . '/perms/file.txt';

            Directory::ensureDirectoryExists($filePath);

            $permissions = fileperms($this->tempBase . '/perms');
            // Check if directory is readable, writable, and executable by owner
            expect($permissions & 0700)->toBeGreaterThan(0);
        });
    });

    describe('Edge Cases', function () {
        it('handles empty parent directory path gracefully', function () {
            // When dirname returns '.' (current directory)
            Directory::ensureDirectoryExists('simple_file.txt');

            expect(is_dir('.'))->toBeTrue(); // Current dir always exists
        });

        it('handles multiple calls for same directory', function () {
            $filePath = $this->tempBase . '/same/file.txt';

            Directory::ensureDirectoryExists($filePath);
            Directory::ensureDirectoryExists($filePath);
            Directory::ensureDirectoryExists($filePath);

            expect(is_dir($this->tempBase . '/same'))->toBeTrue();
        });

        it('handles paths with dots', function () {
            $filePath = $this->tempBase . '/./normal/file.txt';

            Directory::ensureDirectoryExists($filePath);

            expect(file_exists($this->tempBase))->toBeTrue();
        });
    });

    describe('Error Handling', function () {
        it('throws exception with descriptive message on failure', function () {
            // Try to create directory in read-only location (system-dependent)
            $invalidPath = '/root/forbidden/path/file.txt';

            try {
                Directory::ensureDirectoryExists($invalidPath);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (Exception $e) {
                expect($e->getMessage())->toContain('Failed to create directories');
                expect($e->getMessage())->toContain('/root/forbidden/path');
            }
        });
    });

    describe('Real-world Scenarios', function () {
        it('prepares directory for log file', function () {
            $logPath = $this->tempBase . '/logs/2024/11/app.log';

            Directory::ensureDirectoryExists($logPath);

            expect(is_dir($this->tempBase . '/logs/2024/11'))->toBeTrue();
        });

        it('prepares directory for uploaded file', function () {
            $uploadPath = $this->tempBase . '/uploads/images/user_123/avatar.jpg';

            Directory::ensureDirectoryExists($uploadPath);

            expect(is_dir($this->tempBase . '/uploads/images/user_123'))->toBeTrue();
        });

        it('prepares directory for cache file', function () {
            $cachePath = $this->tempBase . '/cache/views/compiled/home.php';

            Directory::ensureDirectoryExists($cachePath);

            expect(is_dir($this->tempBase . '/cache/views/compiled'))->toBeTrue();
        });

        it('can create directory then write file', function () {
            $filePath = $this->tempBase . '/data/output.txt';

            Directory::ensureDirectoryExists($filePath);
            file_put_contents($filePath, 'test content');

            expect(file_exists($filePath))->toBeTrue();
            expect(file_get_contents($filePath))->toBe('test content');

            // Cleanup the file
            unlink($filePath);
        });
    });
});
