<?php

namespace QUITest\QUI;

/**
 * Class SystemTest
 * @package QUITests\QUI
 */
class SystemTest extends \PHPUnit_Framework_TestCase
{
    public function testGetProtocol()
    {
        $protocol = \QUI\Utils\System::getProtocol();

        if ($protocol !== 'http://' && $protocol !== 'https://') {
            $this->fail('unknown protocol');
        }

        $_SERVER['HTTPS'] = 'on';
        $protocol         = \QUI\Utils\System::getProtocol();

        if ($protocol !== 'https://') {
            $this->fail('no https');
        }
    }

    public function testGetUploadMaxFileSize()
    {
        $max = \QUI\Utils\System::getUploadMaxFileSize();

        if (!$max) {
            $this->fail('something went wrong at \QUI\Utils\System::getUploadMaxFileSize');
        }
    }

    public function testMemUsageToHigh()
    {
        if (\QUI\Utils\System::memUsageToHigh()) {
            $this->fail('memUsageToHigh is to high');
        }
    }

    public function testGetClientIP()
    {
        $cl_ip = \QUI\Utils\System::getClientIP();

        // @todo how can i test the ip?
    }
}
