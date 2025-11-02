<?php

namespace Tests\Shared\Stubs;

use Lalaz\View\Contracts\TemplateEngineInterface;

/**
 * Mock TemplateEngine for testing purposes
 */
class MockTemplateEngine implements TemplateEngineInterface
{
    public function render(string $template, array $data = []): string
    {
        return "MockRendered: {$template}";
    }
}
