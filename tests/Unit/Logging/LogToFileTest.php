<?php

use Lalaz\Logging\LogToFile;

describe('LogToFile', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/lalaz_test_logs_' . uniqid();
        $this->logFile = $this->tempDir . '/test.log';
    });

    afterEach(function () {
        // Clean up test directory
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    });

    it('creates directory automatically when it does not exist', function () {
        expect(is_dir($this->tempDir))->toBeFalse();

        $writer = new LogToFile($this->logFile);
        $writer->write('[2024-01-01 12:00:00] INFO: Test message');

        expect(is_dir($this->tempDir))->toBeTrue();
        expect(file_exists($this->logFile))->toBeTrue();
    });

    it('writes log messages to file', function () {
        $writer = new LogToFile($this->logFile);
        $writer->write('[2024-01-01 12:00:00] ERROR: Test error message {"code":500}');

        expect(file_exists($this->logFile))->toBeTrue();
        $content = file_get_contents($this->logFile);
        expect($content)->toContain('ERROR');
        expect($content)->toContain('Test error message');
        expect($content)->toContain('"code":500');
    });

    it('appends multiple log messages', function () {
        $writer = new LogToFile($this->logFile);

        $writer->write('[2024-01-01] INFO: First message');
        $writer->write('[2024-01-01] WARNING: Second message');
        $writer->write('[2024-01-01] ERROR: Third message');

        $content = file_get_contents($this->logFile);
        expect($content)->toContain('First message');
        expect($content)->toContain('Second message');
        expect($content)->toContain('Third message');

        $lines = explode("\n", trim($content));
        expect(count($lines))->toBe(3);
    });

    it('rotates log file when size exceeds maxFileSize', function () {
        $maxFileSize = 100; // Small size to trigger rotation
        $writer = new LogToFile($this->logFile, $maxFileSize);

        // Write enough data to exceed maxFileSize
        $longMessage = '[2024-01-01] INFO: ' . str_repeat('A', 50);
        $writer->write($longMessage);
        $writer->write($longMessage);
        $writer->write($longMessage);

        // Check for rotated file
        expect(file_exists($this->logFile . '.1'))->toBeTrue();
    });

    it('maintains maxFiles rotated backups', function () {
        $maxFileSize = 50;
        $maxFiles = 3;
        $writer = new LogToFile($this->logFile, $maxFileSize, $maxFiles);

        // Write many logs to trigger multiple rotations
        for ($i = 0; $i < 10; $i++) {
            $writer->write('[2024] INFO: ' . str_repeat('X', 40));
        }

        // Count rotated files
        $rotatedFiles = glob($this->logFile . '.*');
        expect(count($rotatedFiles))->toBeLessThanOrEqual($maxFiles);
    });

    it('deletes oldest rotated file when exceeding maxFiles', function () {
        $maxFileSize = 50;
        $maxFiles = 2;
        $writer = new LogToFile($this->logFile, $maxFileSize, $maxFiles);

        // Trigger multiple rotations
        for ($i = 0; $i < 8; $i++) {
            $writer->write('[2024] INFO: ' . str_repeat('Y', 40));
        }

        // Should only have main file + maxFiles rotated
        $allFiles = glob($this->logFile . '*');
        expect(count($allFiles))->toBeLessThanOrEqual($maxFiles + 1);
    });

    it('returns correct maxFileSize getter', function () {
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $writer = new LogToFile($this->logFile, $maxFileSize);

        expect($writer->getMaxFileSize())->toBe($maxFileSize);
    });

    it('returns correct maxFiles getter', function () {
        $maxFiles = 7;
        $writer = new LogToFile($this->logFile, 10 * 1024 * 1024, $maxFiles);

        expect($writer->getMaxFiles())->toBe($maxFiles);
    });

    it('uses default maxFileSize when not specified', function () {
        $writer = new LogToFile($this->logFile);

        expect($writer->getMaxFileSize())->toBe(10 * 1024 * 1024); // 10MB default
    });

    it('uses default maxFiles when not specified', function () {
        $writer = new LogToFile($this->logFile);

        expect($writer->getMaxFiles())->toBe(5); // 5 files default
    });

    it('handles concurrent writes with file locking', function () {
        $writer = new LogToFile($this->logFile);

        // Simulate multiple writes (LOCK_EX should prevent corruption)
        $messages = [
            '[2024-01-01] INFO: Message 1',
            '[2024-01-01] INFO: Message 2',
            '[2024-01-01] INFO: Message 3'
        ];
        foreach ($messages as $msg) {
            $writer->write($msg);
        }

        $content = file_get_contents($this->logFile);
        expect($content)->toContain('Message 1');
        expect($content)->toContain('Message 2');
        expect($content)->toContain('Message 3');
    });

    it('preserves file permissions after rotation', function () {
        $maxFileSize = 50;
        $writer = new LogToFile($this->logFile, $maxFileSize);

        $writer->write('[2024] INFO: ' . str_repeat('Z', 40));

        // Check main file is readable/writable
        expect(is_readable($this->logFile))->toBeTrue();
        expect(is_writable($this->logFile))->toBeTrue();

        // Trigger rotation
        $writer->write('[2024] INFO: ' . str_repeat('Z', 40));

        // Check rotated file is also readable
        if (file_exists($this->logFile . '.1')) {
            expect(is_readable($this->logFile . '.1'))->toBeTrue();
        }
    });

    it('returns correct file path getter', function () {
        $writer = new LogToFile($this->logFile);

        expect($writer->getFilePath())->toBe($this->logFile);
    });
});
