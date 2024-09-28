<?php declare(strict_types=1);

namespace Lalaz\Routing\Attribute;

use Attribute;

/**
 * Class Route
 *
 * This class represents a route attribute used to annotate controller methods.
 * It defines the HTTP method and the path for a given route. The attribute allows
 * routes to be defined directly on controller methods, supporting route-based annotations.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Routing\Attribute
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
#[Attribute]
class Route
{
    /** @var string The HTTP method for the route (default: GET) */
    private string $method = 'GET';

    /** @var string The URI path for the route (default: /) */
    private string $path = '/';

    /**
     * Constructor for the Route attribute.
     *
     * @param string $method The HTTP method (GET, POST, PUT, DELETE, etc.).
     * @param string $path The URI path for the route.
     */
    public function __construct(string $method = 'GET', string $path = '/')
    {
        $this->method = $method;
        $this->path = $path;
    }

    /**
     * Gets the HTTP method for the route.
     *
     * @return string The HTTP method (e.g., GET, POST).
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets the URI path for the route.
     *
     * @return string The URI path for the route.
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
