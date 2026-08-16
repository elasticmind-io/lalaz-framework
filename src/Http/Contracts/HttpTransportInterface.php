<?php declare(strict_types=1);

namespace Lalaz\Http\Contracts;

use Lalaz\Http\Client\ApiClientException;

/**
 * Interface HttpTransportInterface
 *
 * Defines the contract for sending HTTP requests using any transport implementation.
 *
 * @package elasticmind\lalaz-framework
 */
interface HttpTransportInterface
{
    /**
     * Sends an HTTP request and returns the response.
     *
     * @param array $config Request configuration including method, url, headers, body, etc.
     * @return array Associative array with keys: 'status' (int) and 'body' (string)
     * @throws ApiClientException If the request fails.
     */
    public function send(array $config): array;
}
