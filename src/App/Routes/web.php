<?php declare(strict_types=1);

use Lalaz\Routing\Route;

use App\Middlewares\LogMiddleware;

Route::use(LogMiddleware::class);

// Public Routes
Route::get('/', 'HomeController@index');
