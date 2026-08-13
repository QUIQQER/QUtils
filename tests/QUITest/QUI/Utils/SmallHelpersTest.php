<?php

namespace QUITest\QUI\Utils;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\ExceptionStack;
use QUI\Utils\BoolHelper;
use QUI\Utils\Math;
use QUI\Utils\Tracking\Timer;

class SmallHelpersTest extends TestCase
{
    #[DataProvider('booleanProvider')]
    public function testJavaScriptBooleanConversion(bool|string|int $value, bool|string $expected): void
    {
        $this->assertSame($expected, BoolHelper::JSBool($value));
    }

    public static function booleanProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [1, true],
            [0, false],
            [2, false],
            ['true', true],
            ['1', true],
            ['false', false],
            ['0', false],
            ['unchanged', 'unchanged']
        ];
    }

    public function testBoolHelperConstructor(): void
    {
        $this->assertTrue((new BoolHelper('value'))->bool);
        $this->assertFalse((new BoolHelper(false))->bool);
    }

    public function testMathHelpers(): void
    {
        $this->assertSame(0, Math::percent(0, 10));
        $this->assertSame(0, Math::percent(10, 0));
        $this->assertSame(33.33, Math::percent(1, 3, 2));
        $this->assertSame([1 => 100, 2 => 50], Math::resize(200, 100, 100));
        $this->assertSame([1 => 50, 2 => 100], Math::resize(100, 200, 100));
        $this->assertSame(50.0, Math::roundUp(50, 5));
        $this->assertSame(55.0, Math::roundUp(52, 5));
        $this->assertSame(50.0, Math::ceilUp(50, 5));
        $this->assertSame(55.0, Math::ceilUp(50.25, 5));
    }

    public function testExceptionStack(): void
    {
        $Stack = new ExceptionStack();
        $this->assertTrue($Stack->isEmpty());

        $Stack->addException(new Exception('first', 10, ['source' => 'one']));
        $Stack->addException(new Exception('second', 20, ['source' => 'two']));

        $this->assertFalse($Stack->isEmpty());
        $this->assertCount(2, $Stack->getExceptionList());
        $this->assertSame("first\nsecond\n", $Stack->getMessage());
        $this->assertSame(20, $Stack->getCode());
        $this->assertSame('first', $Stack->getContext()[0]['Exception']);
        $this->assertSame('two', $Stack->getContext()[1]['source']);
    }

    public function testTimerResults(): void
    {
        $Timer = new Timer();
        $Timer->milestone('start');

        $result = $Timer->resultConsole();
        $this->assertSame('start', $result[0][0]);
        $this->assertSame('finish', $result[1][0]);
        $this->assertArrayHasKey(2, $result[1]);
        $this->assertArrayHasKey(3, $result[1]);

        $html = $Timer->resultStr();
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('Messpunkt', $html);
        $this->assertStringContainsString('finish', $html);
    }
}
