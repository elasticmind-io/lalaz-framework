<?php declare(strict_types=1);

namespace Lalaz\View;

use Lalaz\View\Contracts\TemplateEngineInterface;
use Lalaz\View\Providers\TwigTemplateEngine;
use Lalaz\View\Providers\BladeTemplateEngine;

class TemplateEngine
{
    private static ?TemplateEngineInterface $engine = null;

    public static function init(): void
    {
        if (self::$engine !== null) {
            return;
        }

        $engineName = config('TEMPLATE_ENGINE') ?? 'twig';

        switch ($engineName) {
            case 'twig':
                if (!class_exists(\Twig\Environment::class)) {
                    throw new \Exception("Twig não está instalado. Instale com 'composer require twig/twig'.");
                }
                self::$engine = new TwigTemplateEngine();
                break;

            default:
                throw new \Exception("Engine de template desconhecida: $engineName");
        }
    }

    public static function getEngine(): TemplateEngineInterface
    {
        if (self::$engine === null) {
            throw new \Exception("TemplateManager não foi inicializado.");
        }

        return self::$engine;
    }

    public static function render(string $template, array $data = []): string
    {
        return self::getEngine()->render($template, $data);
    }
}
