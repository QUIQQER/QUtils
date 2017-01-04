<?php

namespace QUITest\QUI\Utils;

use QUI\Utils\StringHelper as StringHelper;

/**
 * Class StringTest
 */
class StringTest extends \PHPUnit_Framework_TestCase
{
    public function testJSString()
    {
    }

    public function testPathinfo()
    {
        try {
            $test = StringHelper::pathinfo('nothing');

            $this->fail(
                'QUI\Utils\StringHelper::pathinfo throws no exception on a none existing file'
            );
        } catch (\QUI\Exception $Exception) {
        }


        $path = StringHelper::pathinfo(__FILE__);

        if (!isset($path['dirname'])) {
            $this->fail('no dir name');
        }

        if (!isset($path['basename'])
            || $path['basename'] != 'StringTest.php'
        ) {
            $this->fail('basename is wrong');
        }

        if (!isset($path['filename']) || $path['filename'] != 'StringTest') {
            $this->fail('filename is wrong');
        }


        if (StringHelper::pathinfo(__FILE__, PATHINFO_BASENAME) != 'StringTest.php') {
            $this->fail('PATHINFO_BASENAME is wrong');
        }

        if (StringHelper::pathinfo(__FILE__, PATHINFO_EXTENSION) != 'php') {
            $this->fail('PATHINFO_EXTENSION is wrong');
        }

        if (StringHelper::pathinfo(__FILE__, PATHINFO_FILENAME) != 'StringTest') {
            $this->fail('PATHINFO_FILENAME is wrong');
        }

        $dirname = StringHelper::pathinfo(__FILE__, PATHINFO_DIRNAME);

        if ($path['dirname'] != $dirname) {
            $this->fail('PATHINFO_DIRNAME is wrong');
        }
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

        $this->assertEquals(false, StringHelper::isValidUTF8(utf8_decode('müll')));
        $this->assertEquals(true, StringHelper::isValidUTF8(utf8_encode('müll')));

        $this->assertEquals(
            'müll',
            StringHelper::toUTF8(utf8_decode('müll'))
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
