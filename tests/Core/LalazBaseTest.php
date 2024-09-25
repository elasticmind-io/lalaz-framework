<?php

use Lalaz\Lalaz;
use Lalaz\Config\Config;
use Lalaz\Data\Database;
use Lalaz\Routing\Router;
use Lalaz\Logging\Logger;
use PHPUnit\Framework\TestCase;

abstract class LalazBaseTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();

        $configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['load'])
            ->getMock();

        $configMock->expects($this->any())
            ->method('load');

        $this->app = $this->getMockBuilder(Lalaz::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initializeDb'])
            ->getMock();

        Lalaz::$rootDir = __DIR__;

        $this->app->expects($this->any())
            ->method('initializeDb')
            ->willReturn($this->createMock(Database::class));
    }
}
