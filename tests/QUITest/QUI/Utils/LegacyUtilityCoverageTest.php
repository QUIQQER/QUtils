<?php

namespace QUITest\QUI\Utils;

use PHPUnit\Framework\TestCase;
use QUI\Archiver\Zip;
use QUI\Database\Exception as DatabaseException;
use QUI\Exception;
use QUI\Utils\Request\Url;
use QUI\Utils\System\Console;
use QUI\Utils\System\File;
use QUI\Utils\Text\DocToText;
use QUI\Utils\Text\PDFToText;
use QUI\Utils\Translation\GetText;
use ZipArchive;

class LegacyUtilityCoverageTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = sys_get_temp_dir() . '/quiqqer-utils-legacy-' . bin2hex(random_bytes(8));
        File::mkdir($this->testDirectory . '/source/nested');
        file_put_contents($this->testDirectory . '/source/one.txt', 'one');
        file_put_contents($this->testDirectory . '/source/nested/two.txt', 'two');
    }

    protected function tearDown(): void
    {
        File::deleteDir($this->testDirectory);
    }

    public function testZipArchiveRoundTrip(): void
    {
        $archive = $this->testDirectory . '/archive.zip';
        $target = $this->testDirectory . '/unpacked';

        $this->assertTrue(Zip::check());
        $this->assertInstanceOf(Zip::class, new Zip());
        Zip::zip($this->testDirectory . '/source', $archive, ['nested/']);
        $this->assertFileExists($archive);

        Zip::unzip($archive, $target);
        $this->assertFileExists($target . '/one.txt');
        $this->assertFileDoesNotExist($target . '/nested/two.txt');
    }

    public function testZipFilesAndFailurePaths(): void
    {
        $archive = $this->testDirectory . '/files.zip';
        Zip::zipFiles([
            $this->testDirectory . '/source/one.txt',
            $this->testDirectory . '/missing.txt'
        ], $archive);

        $ZipArchive = new ZipArchive();
        $this->assertTrue($ZipArchive->open($archive));
        $this->assertSame('one', $ZipArchive->getFromName('one.txt'));
        $ZipArchive->close();

        try {
            Zip::zipFiles([], $this->testDirectory . '/empty.zip');
            $this->fail('An empty file list must be rejected.');
        } catch (Exception) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(Exception::class);
        Zip::unzip($this->testDirectory . '/missing.zip', $this->testDirectory . '/target');
    }

    public function testGetTextPathLanguageAndFallback(): void
    {
        $GetText = new GetText('de', 'vendor/package', $this->testDirectory . '/locale/');
        $this->assertSame('de_DE', $GetText->getAttribute('locale'));
        $this->assertSame('vendor_package', $GetText->getAttribute('domain'));
        $this->assertSame(
            $this->testDirectory . '/locale/de_DE/LC_MESSAGES/vendor_package.mo',
            $GetText->getFile()
        );
        $this->assertFalse($GetText->fileExist());
        $this->assertSame('missing.translation', $GetText->get('missing.translation'));

        $GetText->setLanguage('en_GB');
        $this->assertSame('en_GB', $GetText->getAttribute('locale'));
    }

    public function testDocumentConversionFailuresAndOdtContent(): void
    {
        try {
            DocToText::convert($this->testDirectory . '/missing.odt');
            $this->fail('A missing document must be rejected.');
        } catch (Exception $Exception) {
            $this->assertSame(404, $Exception->getCode());
        }

        $invalid = $this->testDirectory . '/invalid.odt';
        file_put_contents($invalid, 'not a zip');
        try {
            DocToText::convert($invalid);
            $this->fail('An invalid archive must be rejected.');
        } catch (Exception $Exception) {
            $this->assertSame('Unbekanntes Format.', $Exception->getMessage());
        }

        $document = $this->testDirectory . '/document.odt';
        $Archive = new ZipArchive();
        $this->assertTrue($Archive->open($document, ZipArchive::CREATE));
        $Archive->addFromString('content.xml', '<document><p>Hello</p><p>World</p></document>');
        $Archive->close();

        $this->assertSame('Hello World', DocToText::convert($document));
    }

    public function testLegacyDocConversionAndPdfValidation(): void
    {
        $doc = $this->testDirectory . '/document.doc';
        $header = str_repeat("\0", 0xA00);
        $header[0x21C] = chr(6);
        $header[0x21D] = chr(8);
        file_put_contents($doc, $header . 'Hello');

        $this->assertStringContainsString('Hello', DocToText::convertDoc($doc));

        try {
            PDFToText::convert($this->testDirectory . '/missing.pdf');
            $this->fail('A missing PDF must be rejected.');
        } catch (Exception $Exception) {
            $this->assertSame(404, $Exception->getCode());
        }

        $text = $this->testDirectory . '/not-a-pdf.txt';
        file_put_contents($text, 'text');
        $this->expectException(Exception::class);
        PDFToText::convert($text);
    }

    public function testUrlHelpersWithDataUrl(): void
    {
        $file = $this->testDirectory . '/url-source.txt';
        file_put_contents($file, 'hello world');
        $url = 'file://' . $file;
        $Curl = Url::curl($url, [CURLOPT_TIMEOUT => 1]);
        $this->assertInstanceOf(\CurlHandle::class, $Curl);
        $this->assertSame('hello world', Url::exec($Curl));
        $this->assertSame('hello world', Url::get($url));
        $this->assertTrue(Url::search($url, 'world'));
        $this->assertFalse(Url::search($url, 'missing'));
        $this->assertIsArray(Url::getInfo($url));
    }

    public function testConsoleFormattingAndOutput(): void
    {
        $this->assertSame("\033[1;32mOK\033[0m", Console::getColoredString('OK', Console::COLOR_GREEN));
        $this->assertSame(
            "\033[1;37;0;40mText\033[0m",
            Console::getColoredString('Text', Console::COLOR_WHITE, Console::BACKGROUND_BLACK)
        );

        ob_start();
        Console::write('one');
        Console::writeLn('two');
        $this->assertSame('onetwo' . PHP_EOL, ob_get_clean());
    }

    public function testDatabaseExceptionNormalizesStringCode(): void
    {
        $Exception = new DatabaseException('failure', '42', ['source' => 'test']);

        $this->assertSame(42, $Exception->getCode());
        $this->assertSame('test', $Exception->getContext()['source']);
    }
}
