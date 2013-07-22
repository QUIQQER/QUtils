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
}
