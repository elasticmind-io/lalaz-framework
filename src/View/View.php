<?php declare(strict_types=1);

namespace Lalaz\View;

use Lalaz\Lalaz;
use Lalaz\Http\FlashMessage;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

/**
 * Class View
 *
 * This class handles rendering views using the Twig templating engine.
 * It includes methods for rendering templates, attaching utility functions,
 * and handling common error pages like 404 and 500.
 *
 * @author  Elasticmind <ola@elasticmind.io>
 * @namespace Lalaz\View
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class View
{
    use FlashMessage;

    /**
     * Renders a Twig view template with the provided data.
     *
     * @param string $view The name of the view to render (without .twig extension).
     * @param array $data  An associative array of data to pass to the view.
     *
     * @return void
     */
    public static function render(string $view, array $data = []): void
    {
        $loader = new FilesystemLoader(Lalaz::$rootDir . '/Views');
        $twig = new Environment($loader);

        static::attachUtilFunctions($twig);

        header('Content-Type: text/html');
        http_response_code(200);
        echo $twig->render("$view.twig", $data);
    }

    /**
     * Renders a 404 "Not Found" error page.
     *
     * @param array $data An associative array of data to pass to the 404 error view.
     *
     * @return void
     */
    public static function renderNotFound(array $data = []): void
    {
        static::render('errors/404', $data);
    }

    /**
     * Renders a 500 "Internal Server Error" page.
     *
     * @param array $data An associative array of data to pass to the 500 error view.
     *
     * @return void
     */
    public static function renderError(array $data = []): void
    {
        static::render('errors/500', $data);
    }

    /**
     * Attaches utility functions to the Twig environment.
     *
     * This method adds custom utility functions like flash messages and route URLs
     * to the Twig environment, allowing them to be used in view templates.
     *
     * @param \Twig\Environment $twig The Twig environment to which the functions are attached.
     *
     * @return void
     */
    public static function attachUtilFunctions(Environment $twig): void
    {
        foreach (Utils::all() as $util) {
            $twig->addFunction($util);
        }
    }
}
