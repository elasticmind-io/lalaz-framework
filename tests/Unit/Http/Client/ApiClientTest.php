<?php

use Mockery;
use Lalaz\Http\Client\ApiClient;
use Lalaz\Http\Client\ApiResponse;

describe('ApiClientUnitTests', function () {
    beforeEach(function () {
        $this->apiClientMock = Mockery::mock(ApiClient::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('performs a successful GET request with a valid response', function () {
        // Arrange
        $mockResponse = new ApiResponse(200, ['id' => 1, 'title' => 'foo'], true);

        $this->apiClientMock->shouldReceive('get')
            ->once()
            ->with('/posts/1')
            ->andReturn($mockResponse);

        // Act
        $response = $this->apiClientMock->get('/posts/1');

        // Assert
        expect($response)->toBeInstanceOf(ApiResponse::class)
            ->and($response->statusCode)->toBe(200)
            ->and($response->isSuccessful())->toBeTrue()
            ->and($response->body)->toBeArray()
            ->and($response->body['id'])->toBe(1);
    });

    it('handles a GET request with a 404 response', function () {
        // Arrange
        $mockResponse = new ApiResponse(404, null, false);

        $this->apiClientMock->shouldReceive('get')
            ->once()
            ->with('/posts/99999')
            ->andReturn($mockResponse);

        // Act
        $response = $this->apiClientMock->get('/posts/99999');

        // Assert
        expect($response)->toBeInstanceOf(ApiResponse::class)
            ->and($response->statusCode)->toBe(404)
            ->and($response->isSuccessful())->toBeFalse();
    });

    it('performs a POST request with a valid response', function () {
        // Arrange
        $mockResponse = new ApiResponse(201, ['id' => 101, 'title' => 'foo'], true);

        $this->apiClientMock->shouldReceive('post')
            ->once()
            ->with('/posts', [
                'title' => 'foo',
                'body' => 'bar',
                'userId' => 1
            ])
            ->andReturn($mockResponse);

        // Act
        $response = $this->apiClientMock->post('/posts', [
            'title' => 'foo',
            'body' => 'bar',
            'userId' => 1
        ]);

        // Assert
        expect($response)->toBeInstanceOf(ApiResponse::class)
            ->and($response->statusCode)->toBe(201)
            ->and($response->isSuccessful())->toBeTrue()
            ->and($response->body)->toBeArray()
            ->and($response->body['title'])->toBe('foo');
    });

    it('handles a DELETE request with a 500 internal server error', function () {
        // Arrange
        $mockResponse = new ApiResponse(500, ['error' => 'Internal Server Error'], false);

        $this->apiClientMock->shouldReceive('delete')
            ->once()
            ->with('/posts/1')
            ->andReturn($mockResponse);

        // Act
        $response = $this->apiClientMock->delete('/posts/1');

        // Assert
        expect($response)->toBeInstanceOf(ApiResponse::class)
            ->and($response->statusCode)->toBe(500)
            ->and($response->isSuccessful())->toBeFalse()
            ->and($response->body)->toBeArray()
            ->and($response->body)->toHaveKey('error');
    });
});
