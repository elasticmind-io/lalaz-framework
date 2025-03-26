<?php declare(strict_types=1);

namespace Lalaz\View\Providers;

use Lalaz\Lalaz;
use Lalaz\View\Utils;
use Lalaz\View\Contracts\TemplateEngineInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigTemplateEngine implements TemplateEngineInterface
{
    private Environment $twig;

    public function __construct()
    {
        $viewsPath = config('VIEWS_PATH') ?: '/Views';
        $loader = new FilesystemLoader(Lalaz::appDirectory() . $viewsPath);
        $cacheViews = false;

        $this->twig = new Environment($loader);
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
     * This method adds custom utility functions like flash messages and route URLs
     * to the Twig environment, allowing them to be used in view templates.
     *
     * @return void
     */
    private function attachUtilFunctions(): void
    {
        foreach (Utils::all() as $util) {
            $this->twig->addFunction($util);
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
