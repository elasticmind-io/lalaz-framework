<?php

namespace Tests\Shared\Stubs;

/**
 * Mock implementation of Twig\Environment for testing purposes
 */
class MockTwigEnvironment
{
    private array $functions = [];
    private $loader;

    public function __construct($loader)
    {
        $this->loader = $loader;
    }

    public function addFunction($function): void
    {
        $this->functions[] = $function;
    }

    public function render(string $name, array $context = []): string
    {
        return "Rendered: {$name}";
    }
}
