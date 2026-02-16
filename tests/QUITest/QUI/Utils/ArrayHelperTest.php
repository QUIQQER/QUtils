<?php

namespace QUITest\QUI\Utils;

use QUI\Utils\ArrayHelper as ArrayHelper;

/**
 * Class ArrayHelperTest
 */
class ArrayHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testIsAssoc()
    {
        $assoc = [
            'test' => 'test'
        ];

        $array = ['test'];

        $this->assertFalse(ArrayHelper::isAssoc($array), 'Error: Standard array is no assoc array');
        $this->assertTrue(ArrayHelper::isAssoc($assoc), 'Error: Assoc Array is not as an assoc array identified');
    }

    public function testToAssoc()
    {
        $array = ['test', 'test2'];
        $assoc = ArrayHelper::toAssoc($array);

        $this->assertTrue(ArrayHelper::isAssoc($assoc), 'Error: testToAssoc');
    }

    public function testObjectToArray()
    {
        $obj = ArrayHelper::arrayToObject([
            'a' => 'A',
            'b' => 'B',
            'c' => [
                'a' => 'A2',
                'c' => 'B2'
            ]
        ]);

        $array = ArrayHelper::objectToArray($obj);

        $this->assertArrayHasKey('a', $array);
        $this->assertSame('A', $array['a']);
        $this->assertArrayHasKey('b', $array);
        $this->assertSame('B', $array['b']);
    }

    public function testObjectToArrayInvalidTypeInt()
    {
        $this->expectException(\TypeError::class);
        ArrayHelper::objectToArray(42);
    }

    public function testObjectToArrayInvalidTypeString()
    {
        $this->expectException(\TypeError::class);
        ArrayHelper::objectToArray('string');
    }

    public function testArrayToObject()
    {
        $result = ArrayHelper::arrayToObject([
            'a' => 'A',
            'b' => 'B'
        ]);

        $this->assertIsObject($result);
        $this->assertSame('A', $result->a);
        $this->assertSame('B', $result->b);
    }

    public function testCleanupFromString()
    {
        $result = ArrayHelper::cleanup('a,b,b,,0,c');
        $result = array_values($result);

        $this->assertSame(['a', 'b', 'c'], $result);
    }

    public function testCleanupFromInvalidValue()
    {
        $this->assertSame([], ArrayHelper::cleanup(42));
    }
}
