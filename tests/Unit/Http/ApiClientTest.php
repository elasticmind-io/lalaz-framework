<?php

use PHPUnit\Framework\TestCase;
use Lalaz\Http\Client\ApiClient;
use Lalaz\Http\Client\ApiResponse;

class ApiClientTest extends TestCase
{
    private string $baseUrl = 'https://jsonplaceholder.typicode.com';

    /**
     * Test that a GET request returns a valid response with status 200.
     */
    public function testGetRequestWithValidResponse()
    {
        // Arrange
        $apiClient = new ApiClient($this->baseUrl);

        // Act
        $response = $apiClient->get('/posts/1');

        // Assert
        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertTrue($response->isSuccessful());
        $this->assertIsArray($response->body);
        $this->assertEquals(1, $response->body['id']);
    }

    /**
     * Test that a GET request returns a 404 response.
     */
    public function testGetRequestWith404Response()
    {
        // Arrange
        $apiClient = new ApiClient($this->baseUrl);

        // Act
        $response = $apiClient->get('/posts/99999'); // Resource does not exist

        // Assert
        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(404, $response->statusCode);
        $this->assertFalse($response->isSuccessful());
    }

    /**
     * Test that a POST request returns a valid response with status 201.
     */
    public function testPostRequestWithValidResponse()
    {
        // Arrange
        $apiClient = new ApiClient($this->baseUrl);

        // Act
        $response = $apiClient->post('/posts', [
            'title' => 'foo',
            'body' => 'bar',
            'userId' => 1
        ]);

        // Assert
        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(201, $response->statusCode); // 201 Created
        $this->assertTrue($response->isSuccessful());
        $this->assertIsArray($response->body);
        $this->assertEquals('foo', $response->body['title']);
    }

    /**
     * Test that a DELETE request returns a 500 error (Internal Server Error).
     */
    public function testDeleteRequestWith500Error()
    {
        // Arrange
        $apiClient = new ApiClient($this->baseUrl);

        // Mocking an Internal Server Error (this won't actually happen in jsonplaceholder, but for real-world scenarios)
        $this->markTestIncomplete('This test simulates an internal server error that jsonplaceholder does not produce.');

        // Act
        $response = $apiClient->delete('/posts/1'); // Simulate error

        // Assert
        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals(500, $response->statusCode);
        $this->assertFalse($response->isSuccessful());
        $this->assertIsArray($response->body);
        $this->assertArrayHasKey('error', $response->body); // Assuming error key in the response body
    }
}
