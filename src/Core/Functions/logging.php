<?php

use Lalaz\Logging\Log;

if (!function_exists('info'))
{
    function info($message, array $context = []): void
    {
        Log::info($message, $context);
    }
}

if (!function_exists('debug'))
{
    function debug($message, array $context = []): void
    {
        Log::debug($message, $context);
    }
}

if (!function_exists('error'))
{
    function error($message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
