<?php declare(strict_types=1);

namespace Lalaz\Http\Client;

use Psr\Log\LoggerInterface;
use Lalaz\Http\Client\Transport\CurlTransport;
use Lalaz\Http\Contracts\HttpTransportInterface;

/**
 * Class ApiClientBuilder
 *
 * Fluent builder to configure and instantiate ApiClient with custom settings.
 *
 * @package elasticmind\lalaz-framework
 */
class HttpClientBuilder
{
    /**
     * @var string
     */
    private string $baseUrl;

    /**
     * @var HttpTransportInterface|null
     */
    private ?HttpTransportInterface $transport = null;

    /**
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger = null;

    /**
     * @var array
     */
    private array $options = [];

    /**
     * Starts the builder with a base URL.
     *
     * @param string $baseUrl
     * @return static
     */
    public static function create(string $baseUrl): self
    {
        $instance = new self();
        $instance->baseUrl = rtrim($baseUrl, '/');
        return $instance;
    }

    /**
     * Sets the transport layer implementation.
     *
     * @param HttpTransportInterface $transport
     * @return $this
     */
    public function withTransport(HttpTransportInterface $transport): self
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Sets the PSR-3 logger implementation.
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Sets a custom timeout in seconds.
     *
     * @param int $seconds
     * @return $this
     */
    public function timeout(int $seconds): self
    {
        $this->options['timeout'] = $seconds;
        return $this;
    }

    /**
     * Sets a custom connect timeout in seconds.
     *
     * @param int $seconds
     * @return $this
     */
    public function connectTimeout(int $seconds): self
    {
        $this->options['connectTimeout'] = $seconds;
        return $this;
    }

    /**
     * Enables or disables SSL verification.
     *
     * @param bool $enabled
     * @return $this
     */
    public function skipSsl(bool $enabled = true): self
    {
        $this->options['skipSsl'] = $enabled;
        return $this;
    }

    /**
     * Defines base headers to be sent with every request.
     *
     * @param array $headers
     * @return $this
     */
    public function baseHeaders(array $headers): self
    {
        $this->options['baseHeaders'] = $headers;
        return $this;
    }

    /**
     * Enables automatic retries.
     *
     * @param int $count
     * @param int $delayMs
     * @return $this
     */
    public function retries(int $count, int $delayMs = 500): self
    {
        $this->options['retries'] = $count;
        $this->options['retryDelayMs'] = $delayMs;
        return $this;
    }

    /**
     * Finalizes the configuration and returns the HttpClient instance.
     *
     * @return HttpClient
     */
    public function build(): HttpClient
    {
        return new HttpClient(
            $this->baseUrl,
            $this->transport ?? new CurlTransport(),
            $this->options,
            $this->logger
        );
    }
}
