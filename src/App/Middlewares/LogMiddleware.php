<?php declare(strict_types=1);

namespace App\Middlewares;

use Lalaz\Http\Middleware;
use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Logging\Log;

class LogMiddleware extends Middleware
{
    public function handle(Request $req, Response $res): void
    {
        Log::info('Request received');
    }
}
