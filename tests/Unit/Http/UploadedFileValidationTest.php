<?php declare(strict_types=1);

use Lalaz\Http\UploadedFile;

beforeEach(function () {
    // Create temporary test files
    $this->tempDir = sys_get_temp_dir() . '/lalaz_test_' . uniqid();
    mkdir($this->tempDir);
});

afterEach(function () {
    // Clean up temporary files
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob("$this->tempDir/*.*"));
        rmdir($this->tempDir);
    }
});

test('validates file with correct extension', function () {
    $tmpFile = $this->tempDir . '/test.txt';
    file_put_contents($tmpFile, 'test content');

    $file = [
        'name' => 'test.txt',
        'type' => 'text/plain',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 12,
    ];

    $uploadedFile = new UploadedFile($file);
    expect($uploadedFile->validate(
        ['text/plain'],
        ['txt'],
        1024
    ))->toBeTrue();
});

test('rejects file with invalid extension', function () {
    $tmpFile = $this->tempDir . '/test.exe';
    file_put_contents($tmpFile, 'MZ'); // Fake executable

    $file = [
        'name' => 'test.exe',
        'type' => 'application/x-msdownload',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 2,
    ];

    $uploadedFile = new UploadedFile($file);

    expect(fn() => $uploadedFile->validate(
        ['application/x-msdownload'],
        ['txt', 'pdf', 'jpg'],
        1024
    ))->toThrow(Exception::class, 'Invalid file extension');
});

test('validates file size limit', function () {
    $tmpFile = $this->tempDir . '/large.txt';
    file_put_contents($tmpFile, str_repeat('a', 2000));

    $file = [
        'name' => 'large.txt',
        'type' => 'text/plain',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 2000,
    ];

    $uploadedFile = new UploadedFile($file);

    expect(fn() => $uploadedFile->validate(
        ['text/plain'],
        ['txt'],
        1024 // Max 1KB
    ))->toThrow(Exception::class, 'File size exceeds limit');
});

test('validates magic bytes match declared MIME type', function () {
    // Create a real PNG file (1x1 transparent PNG)
    $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $tmpFile = $this->tempDir . '/test.png';
    file_put_contents($tmpFile, $pngData);

    $file = [
        'name' => 'test.png',
        'type' => 'image/png',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($pngData),
    ];

    $uploadedFile = new UploadedFile($file);
    expect($uploadedFile->validate(
        ['image/png'],
        ['png'],
        10240
    ))->toBeTrue();
});

test('detects MIME type spoofing via magic bytes', function () {
    // Create a text file but declare it as PNG
    $tmpFile = $this->tempDir . '/fake.png';
    file_put_contents($tmpFile, 'This is not a PNG file');

    $file = [
        'name' => 'fake.png',
        'type' => 'image/png', // Spoofed MIME type
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 23,
    ];

    $uploadedFile = new UploadedFile($file);

    expect(fn() => $uploadedFile->validate(
        ['image/png'],
        ['png'],
        1024,
        true // Validate magic bytes
    ))->toThrow(Exception::class, 'File content does not match declared type');
});

test('allows skipping magic bytes validation', function () {
    // Create a text file but declare it as PNG
    $tmpFile = $this->tempDir . '/fake.png';
    file_put_contents($tmpFile, 'This is not a PNG file');

    $file = [
        'name' => 'fake.png',
        'type' => 'image/png',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 23,
    ];

    $uploadedFile = new UploadedFile($file);

    // Should pass when magic bytes validation is disabled
    expect($uploadedFile->validate(
        ['image/png'],
        ['png'],
        1024,
        false // Skip magic bytes validation
    ))->toBeTrue();
});

test('validates upload error codes', function () {
    $file = [
        'name' => 'test.txt',
        'type' => 'text/plain',
        'tmp_name' => '',
        'error' => UPLOAD_ERR_INI_SIZE,
        'size' => 0,
    ];

    $uploadedFile = new UploadedFile($file);

    expect(fn() => $uploadedFile->validate(
        ['text/plain'],
        ['txt'],
        1024
    ))->toThrow(Exception::class, 'File upload failed with error code');
});

test('case insensitive extension matching', function () {
    $tmpFile = $this->tempDir . '/test.TXT';
    file_put_contents($tmpFile, 'test content');

    $file = [
        'name' => 'test.TXT',
        'type' => 'text/plain',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 12,
    ];

    $uploadedFile = new UploadedFile($file);
    expect($uploadedFile->validate(
        ['text/plain'],
        ['txt'], // lowercase allowed
        1024
    ))->toBeTrue();
});

test('validates PDF file with magic bytes', function () {
    // Create a minimal valid PDF
    $pdfData = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";
    $tmpFile = $this->tempDir . '/test.pdf';
    file_put_contents($tmpFile, $pdfData);

    $file = [
        'name' => 'test.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($pdfData),
    ];

    $uploadedFile = new UploadedFile($file);
    expect($uploadedFile->validate(
        ['application/pdf'],
        ['pdf'],
        10240
    ))->toBeTrue();
});

test('detects PDF extension with text content', function () {
    // Create text file with PDF extension
    $tmpFile = $this->tempDir . '/fake.pdf';
    file_put_contents($tmpFile, 'This is just text, not a PDF');

    $file = [
        'name' => 'fake.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 29,
    ];

    $uploadedFile = new UploadedFile($file);

    expect(fn() => $uploadedFile->validate(
        ['application/pdf'],
        ['pdf'],
        1024,
        true
    ))->toThrow(Exception::class, 'File content does not match declared type');
});

test('validates JPEG with magic bytes', function () {
    // Create minimal JPEG (JFIF header)
    $jpegData = hex2bin('FFD8FFE000104A46494600010101006000600000FFDB004300FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFC2000B080001000101011100FFC40014100100000000000000000000000000000000FFDA0008010100003F00D9');
    $tmpFile = $this->tempDir . '/test.jpg';
    file_put_contents($tmpFile, $jpegData);

    $file = [
        'name' => 'test.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($jpegData),
    ];

    $uploadedFile = new UploadedFile($file);
    expect($uploadedFile->validate(
        ['image/jpeg'],
        ['jpg', 'jpeg'],
        10240
    ))->toBeTrue();
});

test('validates file without extension checks when not specified', function () {
    $tmpFile = $this->tempDir . '/test.unknown';
    file_put_contents($tmpFile, 'some content');

    $file = [
        'name' => 'test.unknown',
        'type' => 'text/plain',
        'tmp_name' => $tmpFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 12,
    ];

    $uploadedFile = new UploadedFile($file);
    // Should pass - no extension restrictions
    expect($uploadedFile->validate(
        ['text/plain'], // Match actual detected MIME type
        [], // Empty extensions array - no extension restriction
        1024
    ))->toBeTrue();
});

test('throws error when temp file does not exist', function () {
    $file = [
        'name' => 'test.txt',
        'type' => 'text/plain',
        'tmp_name' => '/nonexistent/file.txt',
        'error' => UPLOAD_ERR_OK,
        'size' => 100,
    ];

    $uploadedFile = new UploadedFile($file);

    expect(fn() => $uploadedFile->validate(
        ['text/plain'],
        ['txt'],
        1024,
        true
    ))->toThrow(Exception::class, 'Uploaded file not found');
});
