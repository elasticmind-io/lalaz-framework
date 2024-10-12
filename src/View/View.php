<?php declare(strict_types=1);

namespace Lalaz\View;

use Throwable;
use Lalaz\Lalaz;
use Lalaz\Core\Config;
use Lalaz\Http\Request;
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
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
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
    public static function render(string $view, array $data = [], $statucCode = 200): void
    {
        $loader = new FilesystemLoader(Lalaz::$rootDir . '/Views');
        $twig = new Environment($loader);

        static::attachUtilFunctions($twig);

        header('Content-Type: text/html');
        http_response_code($statucCode);
        echo $twig->render("$view.twig", $data);
    }

    public static function renderJson(array $data = [], $stausCode = 200): variant_mod
    {
        http_response_code($statucCode);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'An unexpected error occurred. Please try again later.'
        ]);
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
        static::render('errors/404', $data, 404);
    }

    /**
     * Renders a 500 "Internal Server Error" page.
     *
     * @param array $data An associative array of data to pass to the 500 error view.
     *
     * @return void
     */
    public static function renderError(array $data = [], Throwable $exception = null): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        if (Config::isDevelopment() || Config::isDebug()) {
            static::renderDevelopmentError($exception);
            return;
        }

        if (Request::isJsonRequest()) {
            static::renderJson([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);

            return;
        }

        static::render('errors/500', $data, 500);
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

    private static function renderDevelopmentError(Throwable $exception): void
    {
        if (Request::isJsonRequest()) {
            static::renderJson([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ], 500);

            return;
        }

        http_response_code(500);
        echo "<h1>Development Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<h2>Stack Trace:</h2>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    }

    private static function renderDebugInfo()
    {
        if (!Config::isDebug()) {
            return;
        }

        $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;

        echo "<div style='position: fixed; bottom: 0; left: 0; width: 100%; background: #222; color: #fff; z-index: 9999; padding: 10px;'>";
        echo "<strong>Debug Info:</strong> Execution Time: {$executionTime}s, Memory Usage: {$memoryUsage}MB";
        echo "</div>";
    }
}
