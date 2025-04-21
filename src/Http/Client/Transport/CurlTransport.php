<?php declare(strict_types=1);

namespace Lalaz\Http\Client\Transport;

use Lalaz\Http\Client\ApiClientException;

/**
 * Class CurlTransport
 *
 * Default implementation of HttpTransportInterface using PHP's cURL.
 *
 * @package elasticmind\lalaz-framework
 */
class CurlTransport implements HttpTransportInterface
{
    /**
     * Sends an HTTP request using cURL.
     *
     * @param array $config Request configuration.
     * @return array Associative array with 'status' and 'body'.
     * @throws ApiClientException
     */
    public function send(array $config): array
    {
        $ch = curl_init();

        $method          = strtoupper($config['method'] ?? 'GET');
        $url             = $config['url'];
        $headers         = $config['headers'] ?? [];
        $body            = $config['body'] ?? null;
        $timeout         = $config['timeout'] ?? 10;
        $connectTimeout  = $config['connectTimeout'] ?? 5;
        $skipSsl         = $config['skipSsl'] ?? false;

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/json';
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($skipSsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new ApiClientException("cURL error: $error", 0, null);
        }

        curl_close($ch);

        return [
            'status' => $status,
            'body'   => $response,
        ];
    }
}
