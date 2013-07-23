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


        $path = String::pathinfo( __FILE__ );

        if ( !isset( $path['dirname'] ) ) {
            $this->fail( 'no dir name' );
        }

        if ( !isset( $path['basename'] ) || $path['basename'] != 'StringTest.php' ) {
            $this->fail( 'basename is wrong' );
        }

        if ( !isset( $path['filename'] ) || $path['filename'] != 'StringTest' ) {
            $this->fail( 'filename is wrong' );
        }


        if ( String::pathinfo( __FILE__, PATHINFO_BASENAME ) != 'StringTest.php' ) {
            $this->fail( 'PATHINFO_BASENAME is wrong' );
        }

        if ( String::pathinfo( __FILE__, PATHINFO_EXTENSION ) != 'php' ) {
            $this->fail( 'PATHINFO_EXTENSION is wrong' );
        }

        if ( String::pathinfo( __FILE__, PATHINFO_FILENAME ) != 'StringTest' ) {
            $this->fail( 'PATHINFO_FILENAME is wrong' );
        }

        $dirname = String::pathinfo( __FILE__, PATHINFO_DIRNAME );

        if ( $path['dirname'] != $dirname ) {
            $this->fail( 'PATHINFO_DIRNAME is wrong' );
        }
    }

    public function testReplaceDblSlashes()
    {
        $this->assertEquals(
            '/var/www/vhosts/',
            String::replaceDblSlashes('//var//www/vhosts/')
        );
    }

    public function testRemoveLineBreaks()
    {
        $this->assertEquals(
            '   ',
            String::removeLineBreaks("\n  ", " ")
        );
    }

    public function testRemoveDblSigns()
    {
        $this->assertEquals(
            'abc',
            String::removeDblSigns('aabbccc')
        );

        $this->assertEquals(
            '/',
            String::removeDblSigns('///')
        );
    }

    public function testRemoveLastSlash()
    {
        $this->assertEquals(
            '/var/www/vhosts',
            String::removeLastSlash('/var/www/vhosts/')
        );
    }

    public function testFirstToUpper()
    {
        $this->assertEquals(
            'Atesttest',
            String::firstToUpper('ATestTest')
        );
    }

    public function testToUpper()
    {
        $this->assertEquals(
            'ÖLLAMPE',
            String::toUpper('öllampe')
        );
    }

    public function testcountImportantWords()
    {
        $list = String::countImportantWords('Dies ist das Haus vom Nikolaus Nikolaus');

        $this->assertEquals( 2, count( $list ) );
        $this->assertEquals( 2, $list['Nikolaus'] );
        $this->assertEquals( 1, $list['Haus'] );
    }
}
