<?php declare(strict_types=1);

namespace Lalaz;

interface IAppRoutes
{
    static function connect(Server $server): void;
}