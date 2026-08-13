<?php

namespace QUITest\QUI\Utils;

use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\Utils\Installation;
use QUI\Utils\Request\Url;
use QUI\Utils\System\File;
use QUI\Utils\System\Webserver;
use QUI\Utils\Translation\PSpell;
use ReflectionMethod;

class SystemExtrasTest extends TestCase
{
    private array $serverBackup;
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $this->testDirectory = sys_get_temp_dir() . '/quiqqer-utils-system-' . bin2hex(random_bytes(8));
        File::mkdir($this->testDirectory);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        File::deleteDir($this->testDirectory);
    }

    public function testFileSendStreamsContentAndRejectsMissingFile(): void
    {
        try {
            File::send($this->testDirectory . '/missing.txt');
            $this->fail('A missing file must be rejected.');
        } catch (Exception $Exception) {
            $this->assertSame('File not found.', $Exception->getMessage());
        }

        $file = $this->testDirectory . '/download.txt';
        file_put_contents($file, 'download content');

        ob_start();
        File::send($file, 0, 'renamed.txt');
        $this->assertSame('download content', ob_get_clean());
    }

    public function testWebserverDetectionViaCli(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'cli';

        $this->assertSame(Webserver::WEBSERVER_APACHE, Webserver::detectInstalledWebserver());
        $version = Webserver::detectApacheVersion();
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+$/', $version[0]);
    }

    public function testWebserverDetectionUsesServerHeaderCaseInsensitively(): void
    {
        $detectHeader = new ReflectionMethod(Webserver::class, 'detectInstalledWebserverHeader');

        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.62 (Debian)';
        $this->assertSame(Webserver::WEBSERVER_APACHE, $detectHeader->invoke(null));

        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.26.0';
        $this->assertSame(Webserver::WEBSERVER_NGINX, $detectHeader->invoke(null));

        unset($_SERVER['SERVER_SOFTWARE']);
        $this->expectException(Exception::class);
        $detectHeader->invoke(null);
    }

    public function testPSpellReportsMissingExtension(): void
    {
        $this->expectException(Exception::class);
        PSpell::check();
    }

    public function testInstallationMetricsReturnSupportedTypes(): void
    {
        $this->assertIsInt(Installation::getWholeFolderSize(true));
        $this->assertTrue(is_int(Installation::getWholeFolderSizeTimestamp()) || Installation::getWholeFolderSizeTimestamp() === null);
        $this->assertTrue(is_numeric(Installation::getAllFileCount(true)));
        $this->assertTrue(is_numeric(Installation::getAllFileCount()) || Installation::getAllFileCount() === null);
        $this->assertTrue(is_int(Installation::getAllFileCountTimestamp()) || Installation::getAllFileCountTimestamp() === null);
        $this->assertTrue(is_int(Installation::getVarFolderSize()) || Installation::getVarFolderSize() === null);
        $this->assertTrue(is_int(Installation::getVarFolderSizeTimestamp()) || Installation::getVarFolderSizeTimestamp() === null);
    }

    public function testUrlFailurePaths(): void
    {
        $missing = 'file://' . $this->testDirectory . '/missing.txt';

        $this->assertFalse(Url::search($missing, 'content'));
        $this->assertFalse(Url::isReachable($missing));
    }
}
