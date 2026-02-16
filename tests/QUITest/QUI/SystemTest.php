<?php

namespace QUITest\QUI;

use QUI\Utils\System;

/**
 * Class SystemTest
 */
class SystemTest extends \PHPUnit\Framework\TestCase
{
    protected array $serverBackup = [];
    protected int $memoryLimitBackup = 0;

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $this->memoryLimitBackup = System::$memory_limit;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        System::$memory_limit = $this->memoryLimitBackup;
    }

    public function testGetProtocol()
    {
        $protocol = System::getProtocol();
        $this->assertContains($protocol, ['http://', 'https://'], 'unknown protocol');

        $_SERVER['HTTPS'] = 'on';
        $protocol = System::getProtocol();
        $this->assertSame('https://', $protocol, 'no https');
    }

    public function testGetUploadMaxFileSize()
    {
        $max = System::getUploadMaxFileSize();
        $this->assertNotEmpty($max, 'something went wrong at QUI\\Utils\\System::getUploadMaxFileSize');
    }

    public function testMemUsageToHigh()
    {
        $this->assertFalse(System::memUsageToHigh(), 'memUsageToHigh is to high');
    }

    public function testMemUsageToHighWithDisabledLimit()
    {
        System::$memory_limit = 0;
        $this->assertFalse(System::memUsageToHigh());
    }

    public function testGetClientIP()
    {
        $cl_ip = System::getClientIP();
        $this->assertTrue($cl_ip === null || is_string($cl_ip));
    }

    public function testGetClientIPPriority()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.0.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.0.20';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.0.30';

        $this->assertSame('192.168.0.30', System::getClientIP());
    }

    public function testGetClientIPWithCloudflareHeader()
    {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '10.0.0.50';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $this->assertSame('10.0.0.50', System::getClientIP());
    }

    public function testGetMemoryLimitReturnsInt()
    {
        $this->assertIsInt(System::getMemoryLimit());
    }

    public function testIsProtocolSecureWithServerPort()
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '443';

        $this->assertTrue(System::isProtocolSecure());
    }

    public function testIsShellFunctionEnabledWithUnknownFunction()
    {
        $this->assertFalse(System::isShellFunctionEnabled('definitely_not_a_real_function_name'));
    }

    public function testIsSystemFunctionCallableWithUnknownCommand()
    {
        $this->assertFalse(System::isSystemFunctionCallable('definitely_not_a_real_command_name'));
    }
}
