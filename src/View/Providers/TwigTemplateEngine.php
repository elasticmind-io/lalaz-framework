<?php declare(strict_types=1);

namespace Lalaz\View\Providers;

use Lalaz\Lalaz;
use Lalaz\View\ViewHelpers;
use Lalaz\View\Contracts\TemplateEngineInterface;

/**
 * TwigTemplateEngine Provider
 *
 * This provider requires Twig to be installed in your project.
 * Install it with: composer require "twig/twig:^3.0"
 *
 * The framework doesn't include Twig as a dependency to remain
 * template-engine-agnostic. Projects can choose to use Twig,
 * Blade, Plates, or any other template engine.
 *
 * Usage in .env:
 * TEMPLATE_PROVIDER=Lalaz\View\Providers\TwigTemplateEngine
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class TwigTemplateEngine implements TemplateEngineInterface
{
    /**
     * @var \Twig\Environment
     */
    private mixed $twig;

    public function __construct()
    {
        // Check if Twig is installed
        if (!class_exists('\\Twig\\Environment')) {
            throw new \RuntimeException(
                'TwigTemplateEngine requires Twig to be installed. ' .
                'Install it with: composer require "twig/twig:^3.0"'
            );
        }

        $viewsPath = config('VIEWS_PATH') ?: '/Views';

        // Use dynamic class names to avoid type errors when Twig is not installed
        $loaderClass = '\\Twig\\Loader\\FilesystemLoader';
        $envClass = '\\Twig\\Environment';

        /** @var \Twig\Loader\FilesystemLoader $loader */
        $loader = new $loaderClass(Lalaz::appDirectory() . $viewsPath);

        /** @var \Twig\Environment $twig */
        $this->twig = new $envClass($loader);

        $this->attachUtilFunctions();
        $this->attachExtensions();
    }

    public function render(string $template, array $data = []): string
    {
        return $this->twig->render("$template.twig", $data);
    }

    /**
     * Attaches utility functions to the Twig environment.
     *
     * This method converts framework-agnostic ViewHelpers to Twig-specific functions
     * and adds them to the Twig environment, allowing them to be used in view templates.
     *
     * @return void
     */
    private function attachUtilFunctions(): void
    {
        $twigFunctionClass = '\\Twig\\TwigFunction';

        foreach (ViewHelpers::all() as $helper) {
            // Convert framework ViewFunction to Twig TwigFunction
            /** @var \Twig\TwigFunction $function */
            $function = new $twigFunctionClass(
                $helper->getName(),
                $helper->getCallable(),
                $helper->getOptions()
            );

            $this->twig->addFunction($function);
        }
    }

    /**
     * Loads and executes external Twig extension files defined in the VIEW_EXTENSIONS config key.
     *
     * Each file must return a callable that receives the Twig Environment instance,
     * allowing the user to register functions, filters, or global variables.
     *
     * Example of a valid extension file:
     *
     * return function (\Twig\Environment $twig) {
     *     $twig->addFunction(new \Twig\TwigFunction('money', fn($v) => 'R$ ' . number_format($v, 2, ',', '.')));
     *     $twig->addGlobal('now', date('Y-m-d H:i:s'));
     * };
     *
     * @return void
     */
    private function attachExtensions(): void
    {
        $extensionFiles = config('VIEW_EXTENSIONS', []);

        if ($extensionFiles) {
            foreach ($extensionFiles as $file) {
                if (file_exists($file)) {
                    $callback = require $file;
                    if (is_callable($callback)) {
                        $callback($this->twig);
                    }
                }
            }
        }
    }
}
