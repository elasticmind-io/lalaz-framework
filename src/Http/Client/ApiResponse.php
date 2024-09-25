<?php declare(strict_types=1);

namespace Lalaz\Http\Client;

/**
 * Class ApiResponse
 *
 * This class represents the response from an API request.
 * It contains the HTTP status code, the response body, and a success flag.
 */
class ApiResponse
{
    /**
     * @var int The HTTP status code of the API response.
     */
    public int $statusCode;

    /**
     * @var mixed The body of the API response (usually decoded JSON).
     */
    public mixed $body;

    /**
     * @var bool Whether the request was successful (status code 2xx).
     */
    public bool $isSuccess;

    /**
     * Constructor for ApiResponse.
     *
     * @param int $statusCode The HTTP status code.
     * @param mixed $body The response body.
     */
    public function __construct(int $statusCode, mixed $body)
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->isSuccess = $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Returns true if the request was successful (status code 2xx).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->isSuccess;
    }
}
