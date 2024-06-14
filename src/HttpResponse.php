<?php declare(strict_types=1);

namespace Lalaz;

class HttpResponse
{
    use FlashMessage;
    
    private $session;

    public function __construct() {
        $this->session = $_SESSION;
    }

    public function addSession($key, $value): void {
        $this->session = $_SERVER[$key] = $value;
    }

    function flash(
        string $name = '', 
        string $message = '', 
        string $type = ''
    ): HttpResponse {
        if ($name !== '' && $message !== '' && $type !== '') {
            self::createFlashMessage($name, $message, $type);
        }

        return $this;
    }

    public function redirect($url): void {
        header("Location: ${url}");
        exit();
    }

    public function render($view, $params = []): void {
        View::render($view, $params);
        exit();
    }

    public function json($data = [], $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}