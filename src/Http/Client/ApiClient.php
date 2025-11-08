<?php declare(strict_types=1);

namespace Lalaz\Http\Client;

use Psr\Log\LoggerInterface;
use Lalaz\Http\Client\Transport\CurlTransport;
use Lalaz\Http\Contracts\HttpTransportInterface;

/**
 * Class ApiClient
 *
 * This class provides functionality to make RESTful HTTP requests
 * and returns a structured ApiResponse object.
 *
 * @package elasticmind\lalaz-framework
 */
class ApiClient
{
    /**
     * @var string The base URL for the API requests.
     */
    private string $baseUrl;

    /**
     * @var HttpTransportInterface The HTTP transport implementation.
     */
    private HttpTransportInterface $transport;

    /**
     * @var array Configuration options such as timeout, headers, and SSL settings.
     */
    private array $options;

    /**
     * @var LoggerInterface|null Optional logger instance.
     */
    private ?LoggerInterface $logger;

    /**
     * ApiClient constructor.
     *
     * Supports multiple forms:
     * - new ApiClient('https://api')
     * - new ApiClient('https://api', ['skipSsl' => true])
     * - new ApiClient('https://api', new CurlTransport())
     * - new ApiClient('https://api', new CurlTransport(), ['timeout' => 5])
     *
     * @param string $baseUrl
     * @param HttpTransportInterface|array|null $secondParam
     * @param array $options
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        string $baseUrl,
        HttpTransportInterface|array|null $secondParam = null,
        array $options = [],
        ?LoggerInterface $logger = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');

        if ($secondParam instanceof HttpTransportInterface) {
            $this->transport = $secondParam;
        } else {
            $this->transport = new CurlTransport();
            $options = $secondParam ?? [];
        }

        $this->logger = $logger;

        $this->options = array_merge([
            'baseHeaders'    => [],
            'timeout'        => 10,
            'connectTimeout' => 5,
            'skipSsl'        => false,
            'retries'        => 0,
            'retryDelayMs'   => 500,
        ], $options);
    }

    /**
     * Executes a custom HTTP request and returns the response.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return ApiResponse
     * @throws ApiClientException
     */
    public function request(string $method, string $endpoint, array $options = []): ApiResponse
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $mergedHeaders = array_merge($this->options['baseHeaders'], $options['headers'] ?? []);
        $attempts = 0;

        do {
            try {
                $this->logger?->info('[API] Sending request', [
                    'method'  => $method,
                    'url'     => $url,
                    'headers' => $mergedHeaders,
                ]);

                $result = $this->transport->send([
                    'method'         => $method,
                    'url'            => $url,
                    'headers'        => $mergedHeaders,
                    'body'           => $options['body'] ?? null,
                    'timeout'        => $this->options['timeout'],
                    'connectTimeout' => $this->options['connectTimeout'],
                    'skipSsl'        => $this->options['skipSsl'],
                ]);

                return new ApiResponse(
                    $result['status'],
                    $this->decodeBody($result['body'])
                );
            } catch (ApiClientException $e) {
                $this->logger?->error('[API] Request failed', [
                    'message' => $e->getMessage(),
                    'attempt' => $attempts + 1,
                ]);

                if (++$attempts > $this->options['retries']) {
                    throw $e;
                }

                usleep($this->options['retryDelayMs'] * 1000);
            }
        } while (true);
    }

    /**
     * Executes a GET request.
     *
     * @param string $endpoint
     * @param array $headers
     * @return ApiResponse
     */
    public function get(string $endpoint, array $headers = []): ApiResponse
    {
        return $this->request('GET', $endpoint, ['headers' => $headers]);
    }

    /**
     * Executes a POST request with JSON payload.
     *
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return ApiResponse
     */
    public function post(string $endpoint, array $data = [], array $headers = []): ApiResponse
    {
        return $this->request('POST', $endpoint, [
            'body' => json_encode($data),
            'headers' => $headers,
        ]);
    }

    /**
     * Executes a PUT request with JSON payload.
     *
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return ApiResponse
     */
    public function put(string $endpoint, array $data = [], array $headers = []): ApiResponse
    {
        return $this->request('PUT', $endpoint, [
            'body' => json_encode($data),
            'headers' => $headers,
        ]);
    }

    /**
     * Executes a PATCH request with JSON payload.
     *
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return ApiResponse
     */
    public function patch(string $endpoint, array $data = [], array $headers = []): ApiResponse
    {
        return $this->request('PATCH', $endpoint, [
            'body' => json_encode($data),
            'headers' => $headers,
        ]);
    }

    /**
     * Executes a DELETE request.
     *
     * @param string $endpoint
     * @param array $headers
     * @return ApiResponse
     */
    public function delete(string $endpoint, array $headers = []): ApiResponse
    {
        return $this->request('DELETE', $endpoint, ['headers' => $headers]);
    }

    /**
     * Decodes the response body if JSON; otherwise returns the raw string.
     *
     * @param string $raw
     * @return mixed
     */
    private function decodeBody(string $raw): mixed
    {
        $json = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : $raw;
    }
}
