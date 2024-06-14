<?php declare(strict_types=1);

namespace Lalaz;

#[Attribute]
class Route 
{
    private string $method = 'GET';
    private string $paht = '/';

    public function __contruct($method = 'GET', $path = '/') {
        $this->method = $method;
        $this->path = $path;
    }
}