<?php declare(strict_types=1);

namespace Lalaz\Http\Client;

/**
 * Class ApiClient
 *
 * This class provides functionality to make RESTful HTTP requests
 * and returns an ApiResponse that contains the status code and body of the request.
 */
class ApiClient
{
    /**
     * Base URL for the API requests.
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * Constructor for the ApiClient.
     *
     * @param string $baseUrl The base URL for the API requests.
     */
    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Performs a GET request with optional query parameters and headers.
     *
     * @param string $endpoint The API endpoint.
     * @param array $queryParams Optional query parameters to include in the request.
     * @param array $headers Optional headers to include in the request.
     * @return ApiResponse The API response containing the status code and body.
     */
    public function get(string $endpoint, array $queryParams = [], array $headers = []): ApiResponse
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $this->sendRequest('GET', $url, null, $headers);
    }

    /**
     * Performs a POST request with an optional JSON payload and headers.
     *
     * @param string $endpoint The API endpoint.
     * @param array $data Optional JSON payload to include in the request.
     * @param array $headers Optional headers to include in the request.
     * @return ApiResponse The API response containing the status code and body.
     */
    public function post(string $endpoint, array $data = [], array $headers = []): ApiResponse
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $jsonData = json_encode($data);

        return $this->sendRequest('POST', $url, $jsonData, $headers);
    }

    /**
     * Performs a PUT request with an optional JSON payload and headers.
     *
     * @param string $endpoint The API endpoint.
     * @param array $data Optional JSON payload to include in the request.
     * @param array $headers Optional headers to include in the request.
     * @return ApiResponse The API response containing the status code and body.
     */
    public function put(string $endpoint, array $data = [], array $headers = []): ApiResponse
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $jsonData = json_encode($data);

        return $this->sendRequest('PUT', $url, $jsonData, $headers);
    }

    /**
     * Performs a DELETE request with optional headers.
     *
     * @param string $endpoint The API endpoint.
     * @param array $headers Optional headers to include in the request.
     * @return ApiResponse The API response containing the status code and body.
     */
    public function delete(string $endpoint, array $headers = []): ApiResponse
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        return $this->sendRequest('DELETE', $url, null, $headers);
    }

    /**
     * Sends the HTTP request using cURL and returns an ApiResponse.
     *
     * @param string $method The HTTP method (GET, POST, PUT, DELETE, etc.).
     * @param string $url The full URL for the request.
     * @param string|null $data Optional JSON payload for POST/PUT requests.
     * @param array $headers Optional headers to include in the request.
     * @return ApiResponse The API response containing the status code and body.
     */
    private function sendRequest(string $method, string $url, ?string $data = null, array $headers = []): ApiResponse
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($data);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $body = ['error' => curl_error($ch)];
            $statusCode = 500;
        } else {
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $body = json_decode($response, true) ?: $response;
        }

        curl_close($ch);

        return new ApiResponse($statusCode, $body);
    }
}
