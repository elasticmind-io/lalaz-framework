<?php

namespace Tests\Shared\Stubs;

/**
 * Mock implementation of Twig\TwigFunction for testing purposes
 * Used when Twig is not installed as a dependency
 */
class MockTwigFunction
{
    private string $name;
    private $callable;

    public function __construct(string $name, callable $callable)
    {
        $this->name = $name;
        $this->callable = $callable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }
}
