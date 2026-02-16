<?php

namespace QUITest\QUI\Control;

use QUI;
use ReflectionClass;

use function file_put_contents;
use function sys_get_temp_dir;
use function unlink;
use function uniqid;

/**
 * Class ManagerTest
 */
class ManagerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $reflection = new ReflectionClass(QUI\Control\Manager::class);
        $property = $reflection->getProperty('cssFilesLoaded');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function testGetCSSFile()
    {
        $result = QUI\Control\Manager::getCSSFiles();
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testAddCSSFileAndGetCSSFiles(): void
    {
        $file = sys_get_temp_dir() . '/qui-manager-test-' . uniqid('', true) . '.css';
        file_put_contents($file, '.a{color:red;}');

        QUI\Control\Manager::addCSSFile($file);

        $files = QUI\Control\Manager::getCSSFiles();
        $this->assertCount(1, $files);
        $this->assertSame($file, $files[0]);

        unlink($file);
    }

    public function testGetCSSReturnsStyleForExistingFilesOnly(): void
    {
        $file = sys_get_temp_dir() . '/qui-manager-test-' . uniqid('', true) . '.css';
        file_put_contents($file, '.a{color:red;}');
        $missingFile = $file . '.missing';

        QUI\Control\Manager::addCSSFile($missingFile);
        QUI\Control\Manager::addCSSFile($file);

        $css = QUI\Control\Manager::getCSS();
        $this->assertStringContainsString('<style>.a{color:red;}</style>', $css);
        $this->assertStringNotContainsString($missingFile, $css);

        unlink($file);
    }

    public function testSetCSSToHead(): void
    {
        $file = sys_get_temp_dir() . '/qui-manager-test-' . uniqid('', true) . '.css';
        file_put_contents($file, '.a{color:red;}');
        QUI\Control\Manager::addCSSFile($file);

        $html = '<html><head><title>X</title></head><body></body></html>';
        $result = QUI\Control\Manager::setCSSToHead($html);

        $this->assertStringContainsString('<style>.a{color:red;}</style></head>', $result);

        unlink($file);
    }
}
