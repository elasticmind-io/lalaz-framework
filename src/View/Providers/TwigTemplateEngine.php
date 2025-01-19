<?php declare(strict_types=1);

namespace Lalaz\View\Providers;

use Lalaz\View\Utils;
use Lalaz\View\Contracts\TemplateEngineInterface;

use Lalaz\Lalaz;
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
    }

    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
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
}
