<?php

namespace QUITest\QUI\Utils;

use QUI\Utils\StringHelper as StringHelper;

/**
 * Class StringTest
 */
class StringTest extends \PHPUnit\Framework\TestCase
{
    public function testJSString()
    {
        $this->assertSame('value', StringHelper::JSString('value'));
        $this->assertSame('1', StringHelper::JSString(true));
        $this->assertSame('', StringHelper::JSString(false));
    }

    public function testPathinfo()
    {
        $this->expectException(\QUI\Exception::class);
        StringHelper::pathinfo('nothing');
    }

    public function testPathinfoValid()
    {
        $path = StringHelper::pathinfo(__FILE__);
        $this->assertArrayHasKey('dirname', $path, 'no dir name');
        $this->assertArrayHasKey('basename', $path);
        $this->assertSame('StringTest.php', $path['basename'], 'basename is wrong');
        $this->assertArrayHasKey('filename', $path);
        $this->assertSame('StringTest', $path['filename'], 'filename is wrong');
        $this->assertSame('StringTest.php', StringHelper::pathinfo(__FILE__, PATHINFO_BASENAME), 'PATHINFO_BASENAME is wrong');
        $this->assertSame('php', StringHelper::pathinfo(__FILE__, PATHINFO_EXTENSION), 'PATHINFO_EXTENSION is wrong');
        $this->assertSame('StringTest', StringHelper::pathinfo(__FILE__, PATHINFO_FILENAME), 'PATHINFO_FILENAME is wrong');

        $dirname = StringHelper::pathinfo(__FILE__, PATHINFO_DIRNAME);
        $this->assertSame($dirname, $path['dirname'], 'PATHINFO_DIRNAME is wrong');
    }

    public function testReplaceDblSlashes()
    {
        $this->assertEquals(
            '/var/www/vhosts/',
            StringHelper::replaceDblSlashes('//var//www/vhosts/')
        );
    }

    public function testRemoveLineBreaks()
    {
        $this->assertEquals(
            '   ',
            StringHelper::removeLineBreaks("\n  ", " ")
        );
    }

    public function testRemoveDblSigns()
    {
        $this->assertEquals(
            'abc',
            StringHelper::removeDblSigns('aabbccc')
        );

        $this->assertEquals(
            '/',
            StringHelper::removeDblSigns('///')
        );

        $this->assertEquals(
            '/ ',
            StringHelper::removeDblSigns('/// ')
        );

        $this->assertEquals(
            '#',
            StringHelper::removeDblSigns('#')
        );

        $this->assertEquals(
            '[]',
            StringHelper::removeDblSigns('[[]]')
        );
    }

    public function testRemoveLastSlash()
    {
        $this->assertEquals(
            '/var/www/vhosts',
            StringHelper::removeLastSlash('/var/www/vhosts/')
        );
    }

    public function testFirstToUpper()
    {
        $this->assertEquals(
            'Atesttest',
            StringHelper::firstToUpper('ATestTest')
        );
    }

    public function testToUpper()
    {
        $this->assertEquals(
            'ÖLLAMPE',
            StringHelper::toUpper('öllampe')
        );
    }

    public function testGetUrlAttributes()
    {
        $attr
            = StringHelper::getUrlAttributes('index.php?id=1&param1=test&param2=hallo');

        $this->assertArrayHasKey('id', $attr);
        $this->assertArrayHasKey('param1', $attr);
        $this->assertArrayHasKey('param2', $attr);

        $attr = StringHelper::getUrlAttributes('index.php');

        $this->assertEquals(0, count($attr));
    }

    public function testGetHTMLAttributes()
    {
        $attr = StringHelper::getHTMLAttributes(
            '<img class="cssclass" id="unique" src="image.png" style="border: 1px solid red;" />'
        );

        $this->assertArrayHasKey('class', $attr);
        $this->assertArrayHasKey('id', $attr);
        $this->assertArrayHasKey('src', $attr);
        $this->assertArrayHasKey('style', $attr);
    }

    public function testSplitStyleAttributes()
    {
        $attr = StringHelper::getHTMLAttributes(
            '<img src="image.png" style="border: 1px solid red; margin: 10px; padding: 10px;" />'
        );

        $style = StringHelper::splitStyleAttributes($attr['style']);

        $this->assertArrayHasKey('border', $style);
        $this->assertArrayHasKey('margin', $style);
        $this->assertArrayHasKey('padding', $style);

        $this->assertEquals('1px solid red', $style['border']);
        $this->assertEquals('10px', $style['margin']);
        $this->assertEquals('10px', $style['padding']);
    }

    public function testReplaceLast()
    {
        $result = StringHelper::replaceLast('one', 'three', 'one two one');

        $this->assertEquals('one two three', $result);

        $this->assertEquals(
            'one two one',
            StringHelper::replaceLast('three', 'three', 'one two one')
        );
    }

    public function testUTF8()
    {
        $no_utf8 = utf8_decode('müll');

        $this->assertFalse(StringHelper::isValidUTF8(utf8_decode('müll')));
        $this->assertTrue(StringHelper::isValidUTF8(utf8_encode('müll')));

        $this->assertEquals(
            'müll',
            StringHelper::toUTF8($no_utf8)
        );

        $this->assertEquals(
            'müll',
            StringHelper::toUTF8('müll')
        );
    }

    public function testSentence()
    {
        $text = '
            Lorem ipsum dolor sit amet, consetetur sadipscing elitr.
            sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat,
            sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum!
            Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet?
            Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt
            ut labore et dolore magna aliquyam erat, sed diam voluptua.
            At vero eos et accusam et justo duo dolores et ea rebum.
            Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.';

        $sentence = StringHelper::sentence($text);

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
            StringHelper::sentence($text)
        );

        $this->assertEquals(
            false,
            StringHelper::sentence('Lorem ipsum dolor sit amet')
        );
    }

    public function testNumericConversions(): void
    {
        $this->assertSame(12.5, StringHelper::parseFloat(12.5));
        $this->assertSame(0, StringHelper::parseFloat(''));
        $this->assertSame(1234.56, StringHelper::parseFloat('1.234,56 EUR'));
        $this->assertSame(-12.5, StringHelper::parseFloat('-12.5 EUR'));
        $this->assertSame(0.0, StringHelper::parseFloat('not a number'));
        $this->assertIsString(StringHelper::number2db('1,234.56 EUR'));
    }

    public function testTagCloud(): void
    {
        $result = StringHelper::tagCloud([
            ['url' => '/one', 'tag' => 'One'],
            ['url' => '/two', 'tag' => 'Two'],
            ['url' => '/three', 'tag' => 'Three']
        ], 11, 10);

        $this->assertStringContainsString('href="/one"', $result);
        $this->assertStringContainsString('href="/two"', $result);
        $this->assertStringContainsString('font-size: 10px', $result);
    }

    public function testUrlCanBeReassembled(): void
    {
        $parts = parse_url('https://user:pass@example.com:8443/path?q=1#fragment');

        $this->assertIsArray($parts);
        $this->assertSame(
            'https://user:pass@example.com:8443/path?q=1#fragment',
            StringHelper::unparseUrl($parts)
        );
        $this->assertSame('/relative', StringHelper::unparseUrl(['path' => '/relative']));
    }

    public function testAttributeParsingHandlesQuotedAndPlainValues(): void
    {
        $attributes = StringHelper::getHTMLAttributes('<input disabled=true data-id="42" title = "Title">');

        $this->assertSame('true', $attributes['disabled']);
        $this->assertSame('42', $attributes['data-id']);
        $this->assertSame('Title', $attributes['title']);
        $this->assertSame(['color' => 'red'], StringHelper::splitStyleAttributes('invalid; COLOR: RED'));
    }

    public function testMatchingAndReplacementFromEnd(): void
    {
        $this->assertTrue(StringHelper::match('*.php', 'index.php'));
        $this->assertFalse(StringHelper::match('*.js', 'index.php'));
        $this->assertSame('one two three', StringHelper::strReplaceFromEnd('one', 'three', 'one two one'));
        $this->assertSame('unchanged', StringHelper::strReplaceFromEnd('missing', 'value', 'unchanged'));
    }

    public function testStrftimeCompatibility(): void
    {
        $result = StringHelper::strftime(
            '%d %e %j %u %w %U %V %W %m %C %g %G %y %Y ' .
            '%H %k %I %l %M %p %P %r %R %S %T %z %Z %D %F %s%n%t%%',
            '2024-02-05 13:04:06'
        );

        $this->assertStringContainsString('2024-02-05', $result);
        $this->assertStringContainsString('13:04:06', $result);
        $this->assertStringContainsString("\n\t%", $result);
        $this->assertNotEmpty(StringHelper::strftime('%Y', null));
        $this->assertSame('2024', StringHelper::strftime('%Y', 1707091200));
    }

    public function testStrftimeRejectsInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StringHelper::strftime('%Y', 'not a date');
    }

    public function testStrftimeSupportsLocalizedFormats(): void
    {
        $result = StringHelper::strftime('%A %B %c %x %X', '2024-02-05 13:04:06');

        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString('%A', $result);
        $this->assertStringNotContainsString('%B', $result);
    }

    public function testStrftimeRejectsUnknownFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StringHelper::strftime('%Q', '2024-02-05');
    }
}
