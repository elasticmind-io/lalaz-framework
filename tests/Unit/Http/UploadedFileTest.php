<?php declare(strict_types=1);

use Lalaz\Http\UploadedFile;
use Lalaz\Storage\StorageManager;

describe('UploadedFile', function() {
    beforeEach(function() {
        $this->validFile = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/phptest',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
    });

    describe('Constructor', function() {
        it('creates instance with file array', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            expect($uploadedFile)->toBeInstanceOf(UploadedFile::class);
        });
    });

    describe('File Information', function() {
        it('returns client original name', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            $name = $uploadedFile->getClientOriginalName();

            expect($name)->toBe('test.jpg');
        });

        it('returns original name for different files', function() {
            $file = array_merge($this->validFile, ['name' => 'document.pdf']);
            $uploadedFile = new UploadedFile($file);

            expect($uploadedFile->getClientOriginalName())->toBe('document.pdf');
        });
    });

    describe('Validation', function() {
        it('validates file with correct type', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            $result = $uploadedFile->validate(['image/jpeg', 'image/png']);

            expect($result)->toBe(true);
        });

        it('validates file with correct size', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            $result = $uploadedFile->validate([], 2048);

            expect($result)->toBe(true);
        });

        it('validates file with both type and size', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            $result = $uploadedFile->validate(['image/jpeg'], 2048);

            expect($result)->toBe(true);
        });

        it('throws exception for invalid file type', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            expect(fn() => $uploadedFile->validate(['image/png', 'image/gif']))
                ->toThrow(Exception::class, 'Invalid file type.');
        });

        it('throws exception for file size exceeding limit', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            expect(fn() => $uploadedFile->validate([], 512))
                ->toThrow(Exception::class, 'File size exceeds limit.');
        });

        it('allows validation without restrictions', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            $result = $uploadedFile->validate();

            expect($result)->toBe(true);
        });
    });

    describe('File Storage', function() {
        it('stores file using storage driver', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            // Mock StorageManager
            $mockDriver = Mockery::mock();
            $mockDriver->shouldReceive('upload')
                ->once()
                ->with('uploads/images', '/tmp/phptest')
                ->andReturn('https://storage.example.com/uploads/images/test.jpg');

            $mockStorage = Mockery::mock('overload:' . StorageManager::class);
            $mockStorage->shouldReceive('getDriver')
                ->once()
                ->andReturn($mockDriver);

            $path = $uploadedFile->store('uploads/images');

            expect($path)->toBe('https://storage.example.com/uploads/images/test.jpg');
        });
    });

    describe('Edge Cases', function() {
        it('handles empty file name', function() {
            $file = array_merge($this->validFile, ['name' => '']);
            $uploadedFile = new UploadedFile($file);

            expect($uploadedFile->getClientOriginalName())->toBe('');
        });

        it('handles zero size file', function() {
            $file = array_merge($this->validFile, ['size' => 0]);
            $uploadedFile = new UploadedFile($file);

            // Zero size file with any limit should pass validation (not greater than)
            $result = $uploadedFile->validate([], 1024);

            expect($result)->toBe(true);
        });        it('validates large file size', function() {
            $file = array_merge($this->validFile, ['size' => 10485760]); // 10MB
            $uploadedFile = new UploadedFile($file);

            $result = $uploadedFile->validate([], 20971520); // 20MB limit

            expect($result)->toBe(true);
        });

        it('handles special characters in filename', function() {
            $file = array_merge($this->validFile, ['name' => 'файл тест.jpg']);
            $uploadedFile = new UploadedFile($file);

            expect($uploadedFile->getClientOriginalName())->toBe('файл тест.jpg');
        });

        it('validates multiple allowed types', function() {
            $uploadedFile = new UploadedFile($this->validFile);

            $result = $uploadedFile->validate([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ]);

            expect($result)->toBe(true);
        });
    });
});
