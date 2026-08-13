<?php

namespace QUITest\QUI\Utils;

use PHPUnit\Framework\TestCase;
use QUI\Utils\Grid;
use QUI\Utils\Singleton;
use QUI\Utils\System\Debug;

class CoreUtilitiesTest extends TestCase
{
    protected function tearDown(): void
    {
        Debug::$run = false;
        Debug::$debug_memory = false;
        Debug::$times = [];
    }

    public function testGridBuildsDatabaseParameters(): void
    {
        $Grid = new Grid();

        $this->assertSame([], $Grid->parseDBParams('invalid'));
        $this->assertSame(
            ['limit' => '20,10', 'order' => 'title DESC'],
            $Grid->parseDBParams(['perPage' => 10, 'page' => 3, 'sortOn' => 'title', 'sortBy' => 'DESC'])
        );
        $this->assertSame(
            ['limit' => '5', 'order' => 'title '],
            $Grid->parseDBParams(['limit' => '5', 'sortOn' => 'title', 'sortBy' => 'invalid'])
        );
    }

    public function testGridFormatsResults(): void
    {
        $Grid = new Grid(['page' => 2]);
        $data = [['id' => 1], ['id' => 2], ['id' => 3]];

        $this->assertSame(['data' => $data, 'page' => 2, 'total' => 3], $Grid->parseResult($data));
        $this->assertSame(['data' => $data, 'page' => 2, 'total' => 10], $Grid->parseResult($data, 10));
        $this->assertSame(
            ['data' => [3, 4], 'page' => 2, 'total' => 5],
            Grid::getResult([1, 2, 3, 4, 5], 2, 2)
        );
    }

    public function testSingletonReturnsOneInstancePerClass(): void
    {
        $TestSingleton = new class () extends Singleton {
        };
        $OtherTestSingleton = new class () extends Singleton {
        };

        $this->assertSame($TestSingleton::getInstance(), $TestSingleton::getInstance());
        $this->assertNotSame($TestSingleton::getInstance(), $OtherTestSingleton::getInstance());
    }

    public function testDebugMarkersAndOutput(): void
    {
        $_SERVER['REQUEST_URI'] = '/coverage-test';
        $this->assertSame('', Debug::output());
        Debug::marker('ignored');
        $this->assertSame([], Debug::$times);

        Debug::$run = true;
        Debug::$debug_memory = true;
        Debug::marker('start');
        Debug::marker('finish');

        $output = Debug::output();
        $this->assertStringContainsString('/coverage-test', $output);
        $this->assertStringContainsString('start -> finish', $output);
        $this->assertStringContainsString('Overall:', $output);
        $this->assertStringContainsString('MEMORY:', Debug::$times[0]['memory']);
    }
}
