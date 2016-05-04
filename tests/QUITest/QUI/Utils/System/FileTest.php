<?php

use QUI\Utils\System\File as File;
use QUI\Utils\StringHelper as StringHelper;

class FileTest extends PHPUnit_Framework_TestCase
{
    public function testFile()
    {
        $filename = 'test.txt';

        File::unlink($filename);

        $test = File::mkfile('test.txt');
        $this->assertEquals(true, file_exists($filename));

        File::putLineToFile($filename, 'one line');

        $this->assertEquals(
            "one line",
            StringHelper::removeLineBreaks(File::getFileContent($filename))
        );

        File::unlink($filename);
        $this->assertEquals(false, file_exists($filename));
    }

    public function testGetMimeTypes()
    {
        $mimetypes = File::getMimeTypes();

        if (!is_array($mimetypes)) {
            $this->fail('no mimetypes exist');
        }
    }

    public function testFormatSize()
    {
        $info = File::getInfo(dirname(__FILE__).'/FileTest.txt');

        $this->assertEquals('55 KB', File::formatSize($info['filesize']));
    }

    public function testGetBytes()
    {
        $this->assertEquals(135266304, File::getBytes('129M'));
        $this->assertEquals(1073741824, File::getBytes('1G'));
    }

    public function testGetEndingByMimeType()
    {
        $file = dirname(__FILE__).'/FileTest.txt';
        $infos = File::getInfo($file);

        $this->assertEquals(
            File::getEndingByMimeType($infos['extension']),
            'text/plain'
        );
    }

    public function testFileGetContents()
    {
        $content = file_get_contents(dirname(__FILE__).'/FileTest.txt');

        $this->assertEquals(false, File::getFileContent('lalalala.la'));
        $this->assertEquals($content, File::getFileContent(
            dirname(__FILE__).'/FileTest.txt'
        ));
    }

    public function testGetFileSize()
    {
        $this->assertEquals(
            56436,
            File::getFileSize(dirname(__FILE__).'/FileTest.txt')
        );

        $this->assertEquals(
            2168615,
            File::getFileSize(
                'https://cloud.pcsg-server.de/public.php?service=files&t=20c0ec5ddc49b74f52e2452d73e672ea&download'
            )
        );
    }
}
