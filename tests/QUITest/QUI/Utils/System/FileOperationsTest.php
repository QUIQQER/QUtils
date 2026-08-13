<?php

namespace QUITest\QUI\Utils\System;

use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\Utils\System\File;

class FileOperationsTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = sys_get_temp_dir() . '/quiqqer-utils-' . bin2hex(random_bytes(8));
        $this->assertTrue(File::mkdir($this->testDirectory . '/source/nested'));
        file_put_contents($this->testDirectory . '/source/root.txt', 'root');
        file_put_contents($this->testDirectory . '/source/nested/child.txt', 'child');
        file_put_contents($this->testDirectory . '/source/nested/ignored.log', 'log');
    }

    protected function tearDown(): void
    {
        File::deleteDir($this->testDirectory);
    }

    public function testCopyAndMoveFiles(): void
    {
        $source = $this->testDirectory . '/source/root.txt';
        $copy = $this->testDirectory . '/copy.txt';
        $moved = $this->testDirectory . '/moved.txt';

        $this->assertTrue(File::copy($source, $copy));
        $this->assertSame('root', file_get_contents($copy));
        $this->assertTrue(File::move($copy, $moved));
        $this->assertFileDoesNotExist($copy);
        $this->assertFileExists($moved);
    }

    public function testCopyAndMoveRejectConflicts(): void
    {
        $source = $this->testDirectory . '/source/root.txt';

        try {
            File::copy($source, $source);
            $this->fail('Copying over an existing file must fail.');
        } catch (Exception $Exception) {
            $this->assertSame(500, $Exception->getCode());
        }

        try {
            File::copy($this->testDirectory . '/missing.txt', $this->testDirectory . '/target.txt');
            $this->fail('Copying a missing file must fail.');
        } catch (Exception $Exception) {
            $this->assertSame(500, $Exception->getCode());
        }

        $this->expectException(Exception::class);
        File::move($this->testDirectory . '/missing.txt', $this->testDirectory . '/moved.txt');
    }

    public function testDirectoryListingAndSearch(): void
    {
        $source = $this->testDirectory . '/source';

        $this->assertSame([], File::find($this->testDirectory . '/missing', '*.txt'));
        $matches = File::find($source, '*.txt');
        sort($matches);
        $this->assertSame([
            $source . '/nested/child.txt',
            $source . '/root.txt'
        ], $matches);

        $this->assertSame([], File::readDir($this->testDirectory . '/missing'));
        $this->assertContains('root.txt', File::readDir($source, true));
        $this->assertContains('nested', File::readDir($source));

        $File = new File();
        $tree = $File->readDirRecursiv($source);
        $this->assertContains('root.txt', $tree['/']);
        $this->assertContains('child.txt', $tree['nested/']);

        $flat = $File->readDirRecursiv($source, true);
        $this->assertContains('/root.txt', $flat);
        $this->assertContains('nested/child.txt', $flat);
    }

    public function testDirectoryCopyAndDeletion(): void
    {
        $source = $this->testDirectory . '/source';
        $target = $this->testDirectory . '/target';

        $this->assertTrue(File::dircopy($source, $target));
        $this->assertFileExists($target . '/root.txt');
        $this->assertFileExists($target . '/nested/child.txt');
        $this->assertGreaterThan(0, File::getDirectorySize($target));

        $this->assertTrue(File::deleteDir($target . '/root.txt'));
        $this->assertTrue(File::deleteDir($target));
        $this->assertTrue(File::deleteDir($target));
    }

    public function testFileCreationInformationAndSizeHelpers(): void
    {
        $newFile = $this->testDirectory . '/new/path/file.txt';
        $this->assertTrue(File::mkfile($newFile));
        $this->assertFileExists($newFile);
        $this->assertTrue(File::mkfile($newFile));
        File::putLineToFile($newFile, 'line');

        $this->assertSame("line\n", File::getFileContent($newFile));
        $this->assertSame('', File::getFileContent($this->testDirectory . '/missing'));

        $info = File::getInfo($newFile, ['pathinfo' => true, 'filesize' => true, 'mime_type' => true]);
        $this->assertSame('file.txt', $info['basename']);
        $this->assertSame(5, $info['filesize']);
        $this->assertSame('text/plain', $info['mime_type']);

        $this->assertSame('1.5 KB', File::formatSize(1536, 1));
        $this->assertSame(1024, File::getBytes('1K'));
        $this->assertSame(42, File::getBytes(42));
        $this->assertSame('', File::getEndingByMimeType('application/not-real'));
        $this->assertTrue(File::checkOpenBaseDir($newFile));
    }

    public function testGetInfoRejectsMissingFile(): void
    {
        $this->expectException(Exception::class);
        File::getInfo($this->testDirectory . '/missing.txt');
    }

    public function testUnlinkHandlesMissingFilesAndRegularFiles(): void
    {
        $file = $this->testDirectory . '/unlink.txt';
        file_put_contents($file, 'delete');

        $this->assertTrue(File::unlink($file));
        $this->assertTrue(File::unlink($file));
    }
}
