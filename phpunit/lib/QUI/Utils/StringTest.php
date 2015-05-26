<?php

use QUI\Utils\String as String;

class StringTest extends PHPUnit_Framework_TestCase
{
    public function testJSString()
    {

    }

    public function testPathinfo()
    {
        try {
            $test = String::pathinfo('nothing');

            $this->fail(
                'QUI\Utils\String::pathinfo throws no exception on a none existing file'
            );

        } catch (\QUI\Exception $Exception) {
        }


        $path = String::pathinfo(__FILE__);

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


        if (String::pathinfo(__FILE__, PATHINFO_BASENAME) != 'StringTest.php') {
            $this->fail('PATHINFO_BASENAME is wrong');
        }

        if (String::pathinfo(__FILE__, PATHINFO_EXTENSION) != 'php') {
            $this->fail('PATHINFO_EXTENSION is wrong');
        }

        if (String::pathinfo(__FILE__, PATHINFO_FILENAME) != 'StringTest') {
            $this->fail('PATHINFO_FILENAME is wrong');
        }

        $dirname = String::pathinfo(__FILE__, PATHINFO_DIRNAME);

        if ($path['dirname'] != $dirname) {
            $this->fail('PATHINFO_DIRNAME is wrong');
        }
    }

    public function testReplaceDblSlashes()
    {
        $this->assertEquals(
            '/var/www/vhosts/',
            String::replaceDblSlashes('//var//www/vhosts/')
        );
    }

    public function testRemoveLineBreaks()
    {
        $this->assertEquals(
            '   ',
            String::removeLineBreaks("\n  ", " ")
        );
    }

    public function testRemoveDblSigns()
    {
        $this->assertEquals(
            'abc',
            String::removeDblSigns('aabbccc')
        );

        $this->assertEquals(
            '/',
            String::removeDblSigns('///')
        );

        $this->assertEquals(
            '/ ',
            String::removeDblSigns('/// ')
        );

        $this->assertEquals(
            '##',
            String::removeDblSigns('#')
        );

        $this->assertEquals(
            '[[]]',
            String::removeDblSigns('[]')
        );
    }

    public function testRemoveLastSlash()
    {
        $this->assertEquals(
            '/var/www/vhosts',
            String::removeLastSlash('/var/www/vhosts/')
        );
    }

    public function testFirstToUpper()
    {
        $this->assertEquals(
            'Atesttest',
            String::firstToUpper('ATestTest')
        );
    }

    public function testToUpper()
    {
        $this->assertEquals(
            'ÖLLAMPE',
            String::toUpper('öllampe')
        );
    }

    public function testGetUrlAttributes()
    {
        $attr
            = String::getUrlAttributes('index.php?id=1&param1=test&param2=hallo');

        $this->assertArrayHasKey('id', $attr);
        $this->assertArrayHasKey('param1', $attr);
        $this->assertArrayHasKey('param2', $attr);

        $attr = String::getUrlAttributes('index.php');

        $this->assertEquals(0, count($attr));
    }

    public function testGetHTMLAttributes()
    {
        $attr = String::getHTMLAttributes(
            '<img class="cssclass" id="unique" src="image.png" style="border: 1px solid red;" />'
        );

        $this->assertArrayHasKey('class', $attr);
        $this->assertArrayHasKey('id', $attr);
        $this->assertArrayHasKey('src', $attr);
        $this->assertArrayHasKey('style', $attr);
    }

    public function testSplitStyleAttributes()
    {
        $attr = String::getHTMLAttributes(
            '<img src="image.png" style="border: 1px solid red; margin: 10px; padding: 10px;" />'
        );

        $style = String::splitStyleAttributes($attr['style']);

        $this->assertArrayHasKey('border', $style);
        $this->assertArrayHasKey('margin', $style);
        $this->assertArrayHasKey('padding', $style);

        $this->assertEquals('1px solid red', $style['border']);
        $this->assertEquals('10px', $style['margin']);
        $this->assertEquals('10px', $style['padding']);
    }

    public function testReplaceLast()
    {
        $result = String::replaceLast('one', 'three', 'one two one');

        $this->assertEquals('one two three', $result);

        $this->assertEquals(
            'one two one',
            String::replaceLast('three', 'three', 'one two one')
        );
    }

    public function testUTF8()
    {
        $no_utf8 = utf8_decode('müll');

        $this->assertEquals(false, String::isValidUTF8(utf8_decode('müll')));
        $this->assertEquals(true, String::isValidUTF8(utf8_encode('müll')));

        $this->assertEquals(
            'müll',
            String::toUTF8(utf8_decode('müll'))
        );

        $this->assertEquals(
            'müll',
            String::toUTF8('müll')
        );
    }

    public function testSentence()
    {
        $text
            = '
            Lorem ipsum dolor sit amet, consetetur sadipscing elitr.
            sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat,
            sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum!
            Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet?
            Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt
            ut labore et dolore magna aliquyam erat, sed diam voluptua.
            At vero eos et accusam et justo duo dolores et ea rebum.
            Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.';

        $sentence = String::sentence($text);

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
            String::sentence($text)
        );

        $this->assertEquals(
            false,
            String::sentence('Lorem ipsum dolor sit amet')
        );
    }
}
