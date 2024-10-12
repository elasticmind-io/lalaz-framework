<?php

use Lalaz\Core\Loader;

describe('LoaderUnitTests', function() {
    beforeEach(function () {
        // Setup code before each test if needed.
    });

    it('loads all PHP files from a specified directory', function () {
        // Arrange: Create a temporary directory with sample PHP files.
        $tempDir = sys_get_temp_dir() . '/temp_functions_dir';
        mkdir($tempDir);
        $file1 = $tempDir . '/file1.php';
        $file2 = $tempDir . '/file2.php';
        file_put_contents($file1, '<?php function testFunc1() {}');
        file_put_contents($file2, '<?php function testFunc2() {}');

        // Act: Call the loadFiles method.
        Loader::loadFiles($tempDir);

        // Assert: Ensure the functions are available.
        expect(function_exists('testFunc1'))->toBeTrue();
        expect(function_exists('testFunc2'))->toBeTrue();

        // Cleanup: Remove temporary files and directory.
        unlink($file1);
        unlink($file2);
        rmdir($tempDir);
    });

    it('throws an exception if the directory is not found', function () {
        // Arrange: Define a non-existing directory path.
        $invalidDir = '/path/to/non/existing/directory';

        // Act & Assert: Expect an InvalidArgumentException when loadFiles is called.
        expect(fn() => Loader::loadFiles($invalidDir))->toThrow(InvalidArgumentException::class);
    });

    it('loads all core functions from the Functions directory', function () {
        // Act: Call the loadCoreFunctions method.
        // Note: Since the Functions directory is part of the core, ensure that at least one core function is available.
        Loader::loadCoreFunctions();

        // Assert: Check if at least one core function is available.
        // Replace 'some_core_function' with an actual function name that should be present in the Functions directory.
        expect(function_exists('tryCatch'))->toBeTrue();
    });
});
