<?php

namespace QUITest\QUI\Utils\Text;

use QUI\Utils\Text\Word as Word;

/**
 * Class QUIUtilsTextWordTest
 * @package QUITest\QUI\Utils\WordTest
 */
class QUIUtilsTextWordTest extends \PHPUnit_Framework_TestCase
{
    public function testIsUseful()
    {
        $this->assertEquals(false, Word::isUseful(0));
        $this->assertEquals(false, Word::isUseful(''));
    }

    public function testcountImportantWords()
    {
        $list = Word::countImportantWords('Dies ist das Haus vom Nikolaus Nikolaus');

        $this->assertEquals(2, count($list));
        $this->assertEquals(2, $list['Nikolaus']);
        $this->assertEquals(1, $list['Haus']);
    }
}
