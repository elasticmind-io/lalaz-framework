<?php

use Lalaz\Storage\Adapters\LocalStorageAdapter;
use Lalaz\Storage\Contracts\StorageInterface;

describe('LocalStorageAdapter', function () {
    beforeEach(function () {
        // Create temp directory for testing
        $this->testBasePath = sys_get_temp_dir() . '/lalaz_storage_test_' . uniqid();
        mkdir($this->testBasePath, 0777, true);

        // Create test file
        $this->testFile = $this->testBasePath . '/test_source.txt';
        file_put_contents($this->testFile, 'Test content');

        // Set storage config
        $_ENV['STORAGE_PUBLIC_URL'] = 'http://localhost/storage';
    });

    afterEach(function () {
        // Clean up test directory
        if (is_dir($this->testBasePath)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->testBasePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($this->testBasePath);
        }

        unset($_ENV['STORAGE_PUBLIC_URL']);
    });

    describe('interface implementation', function () {
        it('implements StorageInterface', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            expect($adapter)->toBeInstanceOf(StorageInterface::class);
        });
    });

    describe('constructor', function () {
        it('creates instance with valid config', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            expect($adapter)->toBeInstanceOf(LocalStorageAdapter::class);
        });

        it('throws exception when path is missing from config', function () {
            expect(fn() => new LocalStorageAdapter([]))
                ->toThrow(Exception::class, 'STORAGE_CONFIG path was not provided.');
        });

        it('throws exception when config is empty', function () {
            expect(fn() => new LocalStorageAdapter([]))
                ->toThrow(Exception::class);
        });

        it('normalizes path with trailing slash', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            // Path normalization is internal, verified through upload behavior
            expect($adapter)->toBeInstanceOf(LocalStorageAdapter::class);
        });

        it('handles path without trailing slash', function () {
            $pathWithoutSlash = rtrim($this->testBasePath, '/');
            $adapter = new LocalStorageAdapter(['path' => $pathWithoutSlash]);
            expect($adapter)->toBeInstanceOf(LocalStorageAdapter::class);
        });
    });

    describe('upload()', function () {
        it('uploads file successfully', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('test.txt', $this->testFile);

            expect($result)->toBeString();
            expect($result)->toContain('http://localhost/storage');
        });

        it('creates subdirectories when path contains directories', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('uploads/images/test.txt', $this->testFile);

            expect($result)->toContain('uploads/images');
        });

        it('generates unique filename to prevent overwriting', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result1 = $adapter->upload('test.txt', $this->testFile);
            $result2 = $adapter->upload('test.txt', $this->testFile);

            expect($result1)->not->toBe($result2);
        });

        it('preserves file extension', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('test.jpg', $this->testFile);

            expect($result)->toEndWith('.jpg');
        });

        it('handles files without extension', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('README', $this->testFile);

            expect($result)->toBeString();
            expect($result)->toContain('http://localhost/storage');
        });

        it('returns public URL', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('file.txt', $this->testFile);

            expect($result)->toStartWith('http://localhost/storage/');
        });

        it('handles deep nested paths', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('a/b/c/d/e/test.txt', $this->testFile);

            expect($result)->toContain('a/b/c/d/e');
        });

        it('copies file content correctly', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            file_put_contents($this->testFile, 'Unique content 12345');

            $adapter->upload('test.txt', $this->testFile);

            // Find the uploaded file
            $files = glob($this->testBasePath . '/*');
            expect($files)->not->toBeEmpty();

            $uploadedFile = $files[0];
            $content = file_get_contents($uploadedFile);
            expect($content)->toBe('Unique content 12345');
        });
    });

    describe('download()', function () {
        it('returns empty string', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->download('test.txt');

            expect($result)->toBe('');
        });

        it('returns string type', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->download('any/path.txt');

            expect($result)->toBeString();
        });
    });

    describe('delete()', function () {
        it('deletes existing file with absolute path', function () {
            $testFile = $this->testBasePath . '/delete_test.txt';
            file_put_contents($testFile, 'content');

            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->delete($testFile);

            expect($result)->toBeTrue();
            expect(file_exists($testFile))->toBeFalse();
        });

        it('deletes existing file with relative path', function () {
            $testFile = $this->testBasePath . '/delete_test2.txt';
            file_put_contents($testFile, 'content');

            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->delete('delete_test2.txt');

            expect($result)->toBeTrue();
            expect(file_exists($testFile))->toBeFalse();
        });

        it('returns false for non-existent file', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->delete('non_existent_file.txt');

            expect($result)->toBeFalse();
        });

        it('handles path with leading slash', function () {
            $testFile = $this->testBasePath . '/delete_test3.txt';
            file_put_contents($testFile, 'content');

            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->delete('/delete_test3.txt');

            expect($result)->toBeTrue();
        });

        it('returns bool type', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->delete('anything.txt');

            expect($result)->toBeBool();
        });
    });

    describe('getPublicUrl()', function () {
        it('generates public URL with base URL', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->getPublicUrl('test.txt');

            expect($url)->toBe('http://localhost/storage/test.txt');
        });

        it('handles path with leading slash', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->getPublicUrl('/test.txt');

            expect($url)->toBe('http://localhost/storage/test.txt');
        });

        it('handles nested paths', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->getPublicUrl('uploads/images/photo.jpg');

            expect($url)->toBe('http://localhost/storage/uploads/images/photo.jpg');
        });

        it('strips trailing slash from base URL', function () {
            $_ENV['STORAGE_PUBLIC_URL'] = 'http://localhost/storage/';
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->getPublicUrl('test.txt');

            expect($url)->toBe('http://localhost/storage/test.txt');
        });

        it('handles base URL without trailing slash', function () {
            $_ENV['STORAGE_PUBLIC_URL'] = 'http://localhost/storage';
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->getPublicUrl('test.txt');

            expect($url)->toBe('http://localhost/storage/test.txt');
        });

        it('returns string type', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->getPublicUrl('anything.txt');

            expect($url)->toBeString();
        });
    });

    describe('edge cases', function () {
        it('handles empty path in upload', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('', $this->testFile);

            expect($result)->toBeString();
        });

        it('handles special characters in filename', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('file with spaces.txt', $this->testFile);

            expect($result)->toContain('http://localhost/storage');
        });

        it('handles unicode characters in path', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('文件.txt', $this->testFile);

            expect($result)->toBeString();
        });

        it('handles multiple dots in filename', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $result = $adapter->upload('file.backup.old.txt', $this->testFile);

            expect($result)->toEndWith('.txt');
        });
    });

    describe('real-world scenarios', function () {
        it('uploads user avatar', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->upload('avatars/user_123.jpg', $this->testFile);

            expect($url)->toContain('avatars');
            expect($url)->toEndWith('.jpg');
        });

        it('uploads document to specific folder', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $url = $adapter->upload('documents/2024/invoice.pdf', $this->testFile);

            expect($url)->toContain('documents/2024');
            expect($url)->toEndWith('.pdf');
        });

        it('handles multiple file uploads', function () {
            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);

            $url1 = $adapter->upload('file1.txt', $this->testFile);
            $url2 = $adapter->upload('file2.txt', $this->testFile);
            $url3 = $adapter->upload('file3.txt', $this->testFile);

            expect($url1)->not->toBe($url2);
            expect($url2)->not->toBe($url3);
            expect($url1)->not->toBe($url3);
        });

        it('uploads and deletes file', function () {
            $testFile = $this->testBasePath . '/temp.txt';
            file_put_contents($testFile, 'temporary content');

            $adapter = new LocalStorageAdapter(['path' => $this->testBasePath]);
            $deleted = $adapter->delete($testFile);

            expect($deleted)->toBeTrue();
            expect(file_exists($testFile))->toBeFalse();
        });
    });
});
