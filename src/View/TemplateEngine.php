<?php declare(strict_types=1);

namespace Lalaz\View;

use Expcetion;
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

        $engineProvider = config('TEMPLATE_PROVIDER');

        if (!$engineProvider) {
            throw new Exception('TEMPLATE_PROVIDER was not provided.');
        }

        self::$engine = new $engineProvider();
    }

    public static function getEngine(): TemplateEngineInterface
    {
        if (self::$engine === null) {
            throw new Exception("TemplateManager não foi inicializado.");
        }

        return self::$engine;
    }

    public static function render(string $template, array $data = []): string
    {
        return self::getEngine()->render($template, $data);
    }
}
