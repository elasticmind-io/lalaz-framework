<?php declare(strict_types=1);

namespace Lalaz\Exceptions;

/**
 * Class HttpException
 *
 * Base exception for HTTP-related errors with status codes, headers, and context.
 * Uses named constructors for common HTTP errors instead of creating separate exception classes.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class HttpException extends \Exception
{
    protected int $statusCode;
    protected array $headers = [];
    protected array $context = [];

    public function __construct(
        string $message = "",
        int $statusCode = 500,
        array $headers = [],
        array $context = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'statusCode' => $this->statusCode,
            'context' => $this->context,
        ];
    }

    // Named constructors for common HTTP errors

    public static function badRequest(string $message = 'Bad Request', array $context = []): self
    {
        return new self($message, 400, [], $context);
    }

    public static function unauthorized(string $message = 'Unauthorized', array $context = []): self
    {
        return new self($message, 401, [], $context);
    }

    public static function forbidden(string $message = 'Forbidden', array $context = []): self
    {
        return new self($message, 403, [], $context);
    }

    public static function notFound(string $message = 'Not Found', array $context = []): self
    {
        return new self($message, 404, [], $context);
    }

    public static function methodNotAllowed(string $message = 'Method Not Allowed', array $context = []): self
    {
        return new self($message, 405, [], $context);
    }

    public static function csrfMismatch(string $message = 'CSRF token mismatch', array $context = []): self
    {
        return new self($message, 419, [], $context);
    }

    public static function tooManyRequests(string $message = 'Too Many Requests', array $context = [], int $retryAfter = 60): self
    {
        return new self($message, 429, ['Retry-After' => (string)$retryAfter], $context);
    }

    public static function internalServerError(string $message = 'Internal Server Error', array $context = []): self
    {
        return new self($message, 500, [], $context);
    }

    public static function serviceUnavailable(string $message = 'Service Unavailable', array $context = [], int $retryAfter = 60): self
    {
        return new self($message, 503, ['Retry-After' => (string)$retryAfter], $context);
    }
}
