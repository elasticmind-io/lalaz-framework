<?php declare(strict_types=1);

namespace Lalaz;

class Bootstrap
{
    public static function boot(IAppRoutes $appRoutes): void {
        $server = new Server();
        $appRoutes::connect($server);
        
        try {
            $server->run();
        } catch (Exception $ex) {
            print($ex);
        }
    }
}