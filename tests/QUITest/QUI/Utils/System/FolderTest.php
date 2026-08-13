<?php

namespace QUITest\QUI\Utils\System;

use PHPUnit\Framework\TestCase;
use QUI\Utils\System\Folder;

class FolderTest extends TestCase
{
    public function testFolderSizeCanBeCalculatedWithoutCache(): void
    {
        $path = dirname(__DIR__);
        $Folder = $this->getTestableFolder();

        $this->assertGreaterThan(0, $Folder->calculateWithoutCache($path));
        $this->assertSame(0, $Folder->calculateWithoutCache('/path/that/does/not/exist'));
    }

    public function testPathAndCacheKeysAreStable(): void
    {
        $path = dirname(__DIR__);
        $Folder = $this->getTestableFolder();
        $sanitized = $Folder->sanitize($path);

        $this->assertSame(realpath($path), $sanitized);
        $this->assertSame($Folder->sizeKey($path . '/'), $Folder->sizeKey($path));
        $this->assertStringStartsWith('folder_size_', $Folder->sizeKey($path));
        $this->assertStringStartsWith('folder_size_timestamp_', $Folder->timestampKey($path));
    }

    private function getTestableFolder(): Folder
    {
        return new class () extends Folder {
            public function calculateWithoutCache(string $path): int
            {
                return parent::calculateFolderSize($path, true);
            }

            public function sanitize(string $path): string
            {
                return parent::sanitizePath($path);
            }

            public function sizeKey(string $path): string
            {
                return parent::getFolderSizeCacheKey($path);
            }

            public function timestampKey(string $path): string
            {
                return parent::getFolderSizeTimestampCacheKey($path);
            }
        };
    }
}
