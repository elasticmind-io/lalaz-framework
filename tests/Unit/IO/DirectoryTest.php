<?php

use Lalaz\IO\Directory;
use Exception;

describe('ApiClientUnitTests', function () {
    afterEach(function () {
        // Clean up any created directories after each test if needed.
        $tempDir = sys_get_temp_dir() . '/test_dir';
        if (file_exists($tempDir)) {
            rmdir($tempDir);
        }
    });

    it('creates a directory if it does not exist', function () {
        // Arrange: Define a directory path that does not exist.
        $tempDir = sys_get_temp_dir() . '/test_dir/test_file.txt';

        // Act: Ensure the directory exists.
        Directory::ensureDirectoryExists($tempDir);

        // Assert: Check if the directory has been created.
        expect(is_dir(dirname($tempDir)))->toBeTrue();
    });

    it('does not throw an exception if the directory already exists', function () {
        // Arrange: Define a directory path and create it.
        $tempDir = sys_get_temp_dir() . '/test_dir/test_file.txt';
        mkdir(dirname($tempDir), 0755, true);

        // Act & Assert: Ensure no exception is thrown when the directory already exists.
        expect(fn() => Directory::ensureDirectoryExists($tempDir))->not->toThrow(Exception::class);
    });

    it('throws an exception if the directory cannot be created', function () {
        // Arrange: Use an invalid path to simulate a failure in creating a directory.
        // This path should be invalid on most systems.
        $invalidDir = '/invalid_path/test_dir/test_file.txt';

        // Act & Assert: Expect an exception to be thrown when trying to create the directory.
        expect(fn() => Directory::ensureDirectoryExists($invalidDir))->toThrow(Exception::class);
    });
});
