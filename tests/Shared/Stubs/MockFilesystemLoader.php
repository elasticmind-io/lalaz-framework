<?php

namespace Tests\Shared\Stubs;

/**
 * Mock implementation of Twig\Loader\FilesystemLoader for testing purposes
 */
class MockFilesystemLoader
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
