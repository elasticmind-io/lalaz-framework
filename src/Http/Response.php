<?php declare(strict_types=1);

namespace Lalaz\Http;

use stdClass;
use Lalaz\View\View;
use Lalaz\View\ViewContext;

/**
 * Class Response
 *
 * This class provides methods for handling HTTP responses, including session management,
 * cookies, headers, redirects, rendering views, sending JSON responses, and handling flash messages.
 * It is used to build and send HTTP responses back to the client.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Response extends stdClass
{
    use FlashMessage;

    /** @var array $session Stores the session data */
    private $session;

    /**
     * Constructor for the Response class.
     * Initializes the session.
     */
    public function __construct()
    {
        $this->session = $_SESSION;
    }

    /**
     * Registers a variable to be available in the view context.
     *
     * The value can be a direct value or a lazy-loading closure. The variable will
     * be available in all rendered views during the current request.
     *
     * Example:
     * $res->addViewData('user', fn () => Auth::user());
     *
     * @param string $key   The variable name to be available in the view.
     * @param mixed  $value A direct value or a Closure for lazy evaluation.
     * @return self
     */
    public function addViewData(string $key, mixed $value): self
    {
        ViewContext::set($key, $value);
        return $this;
    }

    /**
     * Adds data to the session.
     *
     * @param string $key The key of the session data.
     * @param mixed $value The value to store in the session.
     * @return Response The current Response instance for method chaining.
     */
    public function addSession(string $key, mixed $value): Response
    {
        $_SESSION[$key] = $value;
        $this->session = $_SESSION;
        return $this;
    }

    /**
     * Destroys the current session.
     *
     * @return Response The current Response instance for method chaining.
     */
    public function destroySession(): Response
    {
        session_destroy();
        return $this;
    }

    /**
     * Adds a cookie to the response.
     *
     * @param string $key The name of the cookie.
     * @param string $value The value of the cookie.
     * @param int $expires The expiration time of the cookie (Unix timestamp).
     * @param string $path The path where the cookie is available.
     * @param string $domain The domain where the cookie is available.
     * @param bool $secure Whether the cookie should only be transmitted over a secure HTTPS connection.
     * @param bool $httpOnly Whether the cookie is accessible only through the HTTP protocol.
     * @return Response The current Response instance for method chaining.
     */
    public function addCookie(
        $key,
        $value,
        $expires,
        $path = "/",
        $domain = "",
        $secure = true,
        $httpOnly = true): Response
    {
        setcookie(
            $key,
            $value,
            $expires,
            $path,
            $domain,
            $secure,
            $httpOnly);

        return $this;
    }

    /**
     * Deletes a cookie by setting its expiration time in the past.
     *
     * @param string $key The name of the cookie to delete.
     * @return Response The current Response instance for method chaining.
     */
    public function deleteCookie(string $key): Response
    {
        setcookie($key, '', time() - 3600);
        return $this;
    }

    /**
     * Creates and stores a flash message.
     *
     * @param string $message The content of the flash message.
     * @param string $type The type of the flash message (e.g., success, error).
     * @param string $name The name of the flash message.
     * @return Response The current Response instance for method chaining.
     */
    public function flash(
        string $message = '',
        string $type = '',
        string $name = 'flash'): Response
    {
        if ($name !== '' && $message !== '' && $type !== '') {
            self::createFlashMessage($name, $message, $type);
        }

        return $this;
    }

    /**
     * Adds a header to the response.
     *
     * @param string $name The name of the header.
     * @param string $value The value of the header.
     * @return void
     */
    public function addHeader(string $name, string $value): void
    {
        header("$name: $value", true);
    }

    /**
     * Redirects the client to a new URL.
     *
     * @param string $url The URL to redirect to.
     * @return void
     */
    public function redirect(string $url): void
    {
        $host = $_SERVER['HTTP_HOST'];
        header("Location: {$url}");
        exit();
    }

    /**
     * Renders a view and sends it as the response.
     *
     * @param string $view The view template to render.
     * @param array $params The parameters to pass to the view.
     * @return void
     */
    public function render(string $view, array $params = [], int $statusCode = 200, bool $resetContext = true): void
    {
        $csrfToken = static::generateCsrfToken();
        $this->addSession('csrfToken', $csrfToken);

        $data = [
            ...$params,
            'csrfToken' => $csrfToken
        ];

        header('Content-Type: text/html');
        http_response_code($statusCode);
        echo View::render($view, $data, $resetContext);
    }

    /**
     * Sends a JSON response with the provided data.
     *
     * @param array $data The data to send as JSON.
     * @param int $statusCode The HTTP status code of the response.
     * @return void
     */
    public function json($data = [], $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }

    /**
     * Ends the response and stops further script execution.
     *
     * @return void
     */
    public function end(): void
    {
        exit();
    }

    /**
     * Generates a CSRF token for form submissions.
     *
     * @return string The generated CSRF token.
     */
    private static function generateCsrfToken()
    {
        return bin2hex(random_bytes(35));
    }
}
