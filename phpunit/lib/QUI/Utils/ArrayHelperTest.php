<?php

use QUI\Utils\ArrayHelper as ArrayHelper;

class ArrayHelperTest extends PHPUnit_Framework_TestCase
{
    public function testIsAssoc()
    {
        $assoc = array(
            'test' => 'test'
        );

        $array = array('test');

        if (ArrayHelper::isAssoc($array)) {
            $this->fail('Error: Standard array is no assoc array');
        }

        if (!ArrayHelper::isAssoc($assoc)) {
            $this->fail('Error: Assoc Array is not as an assoc array identified');
        }
    }

    public function testToAssoc()
    {
        $array = array('test', 'test2');
        $assoc = ArrayHelper::toAssoc($array);

        if (!ArrayHelper::isAssoc($assoc)) {
            $this->fail('Error: testToAssoc');
        }
    }

    public function testObjectToArray()
    {
        $obj = ArrayHelper::arrayToObject(array(
            'a' => 'A',
            'b' => 'B',
            'c' => array(
                'a' => 'A2',
                'c' => 'B2'
            )
        ));

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
