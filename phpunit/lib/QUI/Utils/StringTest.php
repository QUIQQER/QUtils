<?php

use QUI\Utils\String as String;

class StringTest extends PHPUnit_Framework_TestCase
{
    public function testJSString()
    {

    }

    public function testPathinfo()
    {
        try
        {
            $test = String::pathinfo( 'nothing' );

            $this->fail(
                'QUI\Utils\String::pathinfo throws no exception on a none existing file'
            );

        } catch ( \QUI\Exception $Exception )
        {

        }
    }
}
