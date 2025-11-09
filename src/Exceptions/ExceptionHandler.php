<?php declare(strict_types=1);

namespace Lalaz\Exceptions;

use Lalaz\Http\Request;
use Lalaz\View\View;

/**
 * Class ExceptionHandler
 *
 * Handles exceptions by rendering appropriate error responses.
 * Supports both JSON and HTML responses based on content negotiation.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class ExceptionHandler
{
    /**
     * Handle any exception and render appropriate response.
     *
     * @param \Throwable $e The exception to handle.
     * @return void
     */
    public static function handle(\Throwable $e): void
    {
        if ($e instanceof HttpException) {
            static::handleHttpException($e);
        } elseif ($e instanceof ValidationException) {
            static::handleValidationException($e);
        } elseif ($e instanceof FrameworkException) {
            static::handleFrameworkException($e);
        } else {
            static::handleUnexpectedException($e);
        }
    }

    /**
     * Handle HTTP exceptions by rendering appropriate error response.
     *
     * @param HttpException $e The HTTP exception to handle.
     * @return void
     */
    private static function handleHttpException(HttpException $e): void
    {
        http_response_code($e->getStatusCode());

        foreach ($e->getHeaders() as $name => $value) {
            header("{$name}: {$value}");
        }

        if (Request::isJsonRequest()) {
            static::renderJson($e->toArray());
        } else {
            $data = [
                'message' => $e->getMessage(),
                'statusCode' => $e->getStatusCode(),
                'context' => $e->getContext(),
            ];
            View::renderError($data, $e);
        }
    }

    /**
     * Handle validation exceptions by rendering appropriate error response.
     *
     * @param ValidationException $e The validation exception to handle.
     * @return void
     */
    private static function handleValidationException(ValidationException $e): void
    {
        http_response_code(422);

        if (Request::isJsonRequest()) {
            static::renderJson($e->toArray());
        } else {
            $data = [
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'context' => $e->getContext(),
            ];
            View::renderError($data, $e);
        }
    }

    /**
     * Handle framework exceptions by rendering appropriate error response.
     *
     * @param FrameworkException $e The framework exception to handle.
     * @return void
     */
    private static function handleFrameworkException(FrameworkException $e): void
    {
        http_response_code(500);

        if (Request::isJsonRequest()) {
            static::renderJson($e->toArray());
        } else {
            $data = [
                'message' => $e->getMessage(),
                'context' => $e->getContext(),
            ];
            View::renderError($data, $e);
        }
    }

    /**
     * Handle unexpected exceptions by rendering generic error response.
     *
     * @param \Throwable $e The unexpected exception to handle.
     * @return void
     */
    private static function handleUnexpectedException(\Throwable $e): void
    {
        http_response_code(500);

        // In production, hide detailed error messages
        $message = getenv('APP_ENV') === 'production'
            ? 'An unexpected error occurred'
            : $e->getMessage();

        if (Request::isJsonRequest()) {
            static::renderJson([
                'error' => true,
                'message' => $message,
                'statusCode' => 500,
            ]);
        } else {
            $data = ['message' => $message];
            View::renderError($data, $e);
        }
    }

    /**
     * Render JSON response.
     *
     * @param array $data The data to encode as JSON.
     * @return void
     */
    private static function renderJson(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
