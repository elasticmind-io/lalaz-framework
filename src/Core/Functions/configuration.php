<?php declare(strict_types=1);

use Lalaz\Core\Config;

if (!function_exists('config'))
{
    function config($key)
    {
        return Config::get($key);
    }
}

if (!function_exists('env'))
{
    function env($key, $defaultValue = null)
    {
        return Config::set($key, $defaultValue);
    }
}
