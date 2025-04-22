<?php declare(strict_types=1);

namespace Lalaz\Http\Client;

use Exception;

/**
 * Class ApiClientException
 *
 * Represents an exception thrown during API requests.
 *
 * @package elasticmind\lalaz-framework
 */
class ApiClientException extends Exception
{
    /**
     * @var int|null The HTTP status code if available.
     */
    protected ?int $httpCode;

    /**
     * @var string|null The raw response body, if available.
     */
    protected ?string $responseBody;

    /**
     * ApiClientException constructor.
     *
     * @param string $message The exception message.
     * @param int $code The internal error code.
     * @param int|null $httpCode Optional HTTP status code.
     * @param string|null $responseBody Optional raw body returned by the API.
     */
    public function __construct(string $message, int $code = 0, ?int $httpCode = null, ?string $responseBody = null)
    {
        parent::__construct($message, $code);
        $this->httpCode = $httpCode;
        $this->responseBody = $responseBody;
    }

    /**
     * Returns the HTTP status code if available.
     *
     * @return int|null
     */
    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    /**
     * Returns the raw response body if available.
     *
     * @return string|null
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
