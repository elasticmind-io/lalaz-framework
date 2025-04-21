<?php

use Lalaz\Http\Client\ApiClient;
use Lalaz\Http\Client\ApiResponse;
use Lalaz\Http\Client\ApiClientBuilder;
use Lalaz\Http\Client\Transport\HttpTransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

describe('ApiClientBuilderUnitTests', function () {
    beforeEach(function () {
        $this->fakeTransport = new class implements HttpTransportInterface {
            public array $lastRequest;

            public function send(array $config): array
            {
                $this->lastRequest = $config;

                return [
                    'status' => 201,
                    'body' => json_encode(['built' => true, 'method' => $config['method']])
                ];
            }
        };
    });

    it('builds an ApiClient with default CurlTransport when none is provided', function () {
        $client = ApiClientBuilder::create('https://api.test')
            ->build();

        expect($client)->toBeInstanceOf(ApiClient::class);
    });

    it('sets timeout, skipSsl and headers via builder', function () {
        $client = ApiClientBuilder::create('https://api.test')
            ->withTransport($this->fakeTransport)
            ->timeout(20)
            ->connectTimeout(5)
            ->skipSsl()
            ->baseHeaders(['Authorization: Bearer 123'])
            ->build();

        $response = $client->post('/test', ['data' => true]);

        /** @var ApiResponse $response */
        expect($response)->toBeInstanceOf(ApiResponse::class)
            ->and($response->statusCode)->toBe(201)
            ->and($response->body['method'])->toBe('POST')
            ->and($this->fakeTransport->lastRequest['timeout'])->toBe(20)
            ->and($this->fakeTransport->lastRequest['connectTimeout'])->toBe(5)
            ->and($this->fakeTransport->lastRequest['skipSsl'])->toBeTrue()
            ->and($this->fakeTransport->lastRequest['headers'])->toContain('Authorization: Bearer 123');
    });

    it('sets a logger using the builder', function () {
        $logger = new class extends NullLogger {
            public array $messages = [];

            public function info($message, array $context = []): void
            {
                $this->messages[] = $message;
            }
        };

        $client = ApiClientBuilder::create('https://api.test')
            ->withTransport($this->fakeTransport)
            ->withLogger($logger)
            ->build();

        $client->get('/log-test');

        expect($logger->messages)->toContain('[API] Sending request');
    });
});
