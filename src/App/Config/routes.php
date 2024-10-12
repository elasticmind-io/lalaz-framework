<?php declare(strict_types=1);

use Lalaz\Routing\Route;

function onRouterInitialized()
{
    // Public Routes
    Route::get('/', 'HomeController@index');
}
