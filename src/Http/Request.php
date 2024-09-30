<?php declare(strict_types=1);

namespace Lalaz\Http;

/**
 * Class Request
 *
 * This class represents an HTTP request and provides methods to access
 * request parameters, body data, session, and cookies. It also handles
 * CSRF token validation for specific HTTP methods.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Http
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class Request
{
    /** @var string The HTTP method of the request (GET, POST, etc.) */
    private $method;

    /** @var array The request headers */
    private $headers;

    /** @var array The parameters from the request (query and route params) */
    private $params;

    /** @var array The body data from the request */
    private $body;

    /** @var array The session data */
    private $session;

    /** @var array The cookies from the request */
    private $cookies;

    /** @var array List of HTTP methods that require CSRF token validation */
    private static $methodsToValidateCsrfToken = ['POST', 'PUT', 'PATCH'];

    /**
     * Constructor for the Request class.
     *
     * Initializes the request parameters, session, body, and cookies.
     * Merges route parameters with query parameters from the URL.
     *
     * @param array $pathParams Parameters passed from the route.
     */
    public function __construct($pathParams = [])
    {
        $this->initializeSession();
        $this->initializeBody();
        $this->initializeCookies();

        $routeAndGetParams = array_merge($pathParams, $_GET);

        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->params = $this->sanitize($routeAndGetParams);
    }

    /**
     * Returns the HTTP method of the request.
     *
     * @return mixed The HTTP method (e.g., GET, POST).
     */
    public function method(): mixed
    {
        return $this->method;
    }

    /**
     * Returns the request parameters.
     *
     * @param string $name Optional. The name of the parameter to retrieve.
     * @return mixed The value of the parameter, or all parameters if no name is provided.
     */
    public function params($name = ''): mixed
    {
        if (strlen($name) === 0) {
            return $this->params;
        }

        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return null;
    }

    /**
     * Returns the current page number based on a parameter.
     *
     * @param string $pageParamName The name of the parameter representing the page.
     * @return int The current page number.
     */
    public function currentPage($pageParamName = 'p'): int
    {
        $p = $this->params($pageParamName);
        return empty($p) ? 1 : intval($p);
    }

    /**
     * Returns the body of the request.
     *
     * @return mixed The body data of the request.
     */
    public function body(): mixed
    {
        return $this->body;
    }

    /**
     * Returns a specific cookie by name.
     *
     * @param string $name The name of the cookie.
     * @return mixed The value of the cookie.
     */
    public function cookie($name): mixed
    {
        return $this->cookies[$name];
    }

    /**
     * Returns a specific session variable by name.
     *
     * @param string $name The name of the session variable.
     * @return mixed The value of the session variable.
     */
    public function session($name): mixed
    {
        return $this->session[$name];
    }

    /**
     * Validates the CSRF token for POST, PUT, and PATCH requests.
     *
     * If the token in the body does not match the session token, the request is terminated.
     *
     * @return void
     */
    public function validateCsrfToken(): void
    {
        if (!in_array($this->method(), static::$methodsToValidateCsrfToken)) {
            return;
        }

        if ($this->body()['csrfToken'] !== $this->session('csrfToken')) {
            die('Request token is not valid!');
        }
    }

    /**
     * Initializes the body of the request.
     *
     * This method checks if there is POST data or raw input (for JSON requests),
     * and sanitizes the input.
     *
     * @return void
     */
    private function initializeBody(): void
    {
        if (!empty($_POST)) {
            $this->body = $this->sanitize($_POST);
            return;
        }

        $this->body = $this->sanitize(json_decode(file_get_contents('php://input')));
    }

    /**
     * Initializes the session.
     *
     * If a session is not already started, this method starts a session.
     *
     * @return void
     */
    private function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->session = $_SESSION;
    }

    /**
     * Initializes the cookies from the request.
     *
     * @return void
     */
    private function initializeCookies(): void
    {
        $this->cookies = $_COOKIE;
    }

    /**
     * Sanitizes input data by filtering strings to prevent potential security issues.
     *
     * @param array|object $data The data to sanitize.
     * @return mixed The sanitized data.
     */
    private function sanitize($data = array()): mixed
    {
        if (empty($data)) return [];
        return filter_var_array($data, FILTER_SANITIZE_STRING);
    }
}
