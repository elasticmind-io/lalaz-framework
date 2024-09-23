<?php declare(strict_types=1);

namespace Lalaz\Http;

/**
 * Class Controller
 *
 * This abstract class provides a base structure for all controllers in the application.
 * It includes a method to dynamically execute actions on the controller with the provided
 * method name and parameters.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Http
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
abstract class Controller
{
    /**
     * Execute an action on the controller.
     *
     * This method dynamically calls the specified action method on the controller, passing
     * the provided parameters to it.
     *
     * @param string $method The name of the method to call on the controller.
     * @param array $parameters The parameters to pass to the method.
     *
     * @return void
     */
    public function callAction($method, $parameters): void
    {
        $this->{$method}(...array_values($parameters));
    }
}
