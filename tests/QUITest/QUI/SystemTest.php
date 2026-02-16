<?php

namespace QUITest\QUI;

/**
 * Class SystemTest
 */
class SystemTest extends \PHPUnit\Framework\TestCase
{
    public function testGetProtocol()
    {
        $protocol = \QUI\Utils\System::getProtocol();
        $this->assertContains($protocol, ['http://', 'https://'], 'unknown protocol');

        $_SERVER['HTTPS'] = 'on';
        $protocol = \QUI\Utils\System::getProtocol();
        $this->assertSame('https://', $protocol, 'no https');
    }

    public function testGetUploadMaxFileSize()
    {
        $max = \QUI\Utils\System::getUploadMaxFileSize();
        $this->assertNotEmpty($max, 'something went wrong at \QUI\Utils\System::getUploadMaxFileSize');
    }

    public function testMemUsageToHigh()
    {
        $this->assertFalse(\QUI\Utils\System::memUsageToHigh(), 'memUsageToHigh is to high');
    }

    public function testGetClientIP()
    {
        $cl_ip = \QUI\Utils\System::getClientIP();
        $this->assertTrue($cl_ip === null || is_string($cl_ip));
    }
}
