<?php

namespace QUITest\QUI\Utils;

use QUI\Utils\ArrayHelper as ArrayHelper;

/**
 * Class ArrayHelperTest
 * @package QUITests\QUI\Utils
 */
class ArrayHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testIsAssoc()
    {
        $assoc = [
            'test' => 'test'
        ];

        $array = ['test'];

        if (ArrayHelper::isAssoc($array)) {
            $this->fail('Error: Standard array is no assoc array');
        }

        if (!ArrayHelper::isAssoc($assoc)) {
            $this->fail('Error: Assoc Array is not as an assoc array identified');
        }
    }

    public function testToAssoc()
    {
        $array = ['test', 'test2'];
        $assoc = ArrayHelper::toAssoc($array);

        if (!ArrayHelper::isAssoc($assoc)) {
            $this->fail('Error: testToAssoc');
        }
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

        if (!isset($array['a']) && $array['a'] != 'A') {
            $this->fail('ArrayHelper::objectToArray( fail');
        }

        if (!isset($array['b']) && $array['b'] != 'B') {
            $this->fail('ArrayHelper::objectToArray( fail');
        }

        // bad values
        if (!is_array(ArrayHelper::objectToArray(42))) {
            $this->fail('ArrayHelper::objectToArray( fail');
        }

        if (!is_array(ArrayHelper::objectToArray('string'))) {
            $this->fail('ArrayHelper::objectToArray( fail');
        }
    }
}
