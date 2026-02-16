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
        $this->markTestSkipped('Legacy test without assertions.');
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
}
