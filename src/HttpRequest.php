<?php declare(strict_types=1);

namespace Lalaz;

class HttpRequest 
{
    private $headers;
    private $params;
    private $body;
    private $session;

    public function __construct($pathParams = []) {
        $this->initializeSession();
        $this->initializeBody();

        $this->params = array_merge($pathParams, $_GET); 
        $this->session = $_SESSION;
    }

    public function params($name) {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return '';
    }

    public function body() {
        return $this->body;
    }

    private function initializeBody(): void {
        if (!empty($_POST)) {
            $this->body = $_POST;
            return;
        }

        $this->body = json_decode(file_get_contents('php://input'));
    }

    private function initializeSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}