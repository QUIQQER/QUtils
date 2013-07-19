<?php

class SystemTest extends PHPUnit_Framework_TestCase
{
    static function testGetProtocol()
    {
        $protocol = \QUI\Utils\System::getProtocol();

        if ( $protocol !== 'http://' && $protocol !== 'https://' ) {
            $this->fail( 'unknown protocol' );
        }

        $_SERVER['HTTPS'] = 'on';
        $protocol         = \QUI\Utils\System::getProtocol();

        if ( $protocol !== 'https://' ) {
            $this->fail( 'no https' );
        }
    }

    static function testGetUploadMaxFileSize()
    {
        $max = \QUI\Utils\System::getUploadMaxFileSize();

        if ( !$max ) {
            $this->fail( 'something went wrong at \QUI\Utils\System::getUploadMaxFileSize' );
        }
    }

    static function testMemUsageToHigh()
    {
        if ( \QUI\Utils\System::memUsageToHigh() ) {
            $this->fail( 'memUsageToHigh is to high' );
        }
    }

    static function testGetClientIP()
    {
        $cl_ip = \QUI\Utils\System::getClientIP();

        // @todo how can i test the ip?
    }
}