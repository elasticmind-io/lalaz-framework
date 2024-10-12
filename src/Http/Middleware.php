<?php declare(strict_types=1);

namespace Lalaz\Http;

/**
 * Class Middleware
 *
 * This abstract class defines the structure for all middleware in the application.
 * Middleware classes that extend this class must implement the `handle()` method to
 * intercept and manipulate HTTP requests and responses.
 *
 * Middleware typically performs tasks such as authentication, logging, or modifying
 * requests before they reach the controller.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
abstract class Middleware
{
    /**
     * Handles an incoming HTTP request and prepares a response.
     *
     * Middleware classes must implement this method to handle the logic
     * for processing the request and preparing the response.
     *
     * @param Request $req The incoming HTTP request.
     * @param Response $res The outgoing HTTP response.
     *
     * @return void
     */
    public abstract function handle(Request $req, Response $res): void;
}
