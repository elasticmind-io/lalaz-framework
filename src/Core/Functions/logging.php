<?php

use Lalaz\Logging\Log;

if (!function_exists('emergency'))
{
    function emergency(string|\Stringable $message, array $context = []): void
    {
        Log::emergency($message, $context);
    }
}

if (!function_exists('alert'))
{
    function alert(string|\Stringable $message, array $context = []): void
    {
        Log::alert($message, $context);
    }
}

if (!function_exists('critical'))
{
    function critical(string|\Stringable $message, array $context = []): void
    {
        Log::critical($message, $context);
    }
}

if (!function_exists('error'))
{
    function error(string|\Stringable $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}

if (!function_exists('warning'))
{
    function warning(string|\Stringable $message, array $context = []): void
    {
        Log::warning($message, $context);
    }
}

if (!function_exists('notice'))
{
    function notice(string|\Stringable $message, array $context = []): void
    {
        Log::notice($message, $context);
    }
}

if (!function_exists('info'))
{
    function info(string|\Stringable $message, array $context = []): void
    {
        Log::info($message, $context);
    }
}

if (!function_exists('debug'))
{
    function debug(string|\Stringable $message, array $context = []): void
    {
        Log::debug($message, $context);
    }
}
