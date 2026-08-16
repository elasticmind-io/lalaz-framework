<?php declare(strict_types=1);

namespace Lalaz\View\Contracts;


interface TemplateEngineInterface
{
    public function render(string $template, array $data = []): string;
}
