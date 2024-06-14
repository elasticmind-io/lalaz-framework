<?php declare(strict_types=1);

namespace Lalaz;

class View
{
	public static function render($view, $data = array()): void {
		$loader = new \Twig\Loader\FilesystemLoader(APP_VIEWS_PATH);
		$twig = new \Twig\Environment($loader);
		$viewWithExtension = $view . APP_VIEW_EXT;
		header('Content-Type: text/html');
        http_response_code(200);
		echo $twig->render($viewWithExtension, $data);
	}

	public static function renderNotFound($data = array()): void {
		http_response_code(404);
        echo '<h1>Page not found</h1>';
	}

	public static function renderError($data = array()): void {
		http_response_code(500);
        echo '<h1>Internal Server Error</h1>';
	}
}