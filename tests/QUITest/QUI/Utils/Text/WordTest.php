<?php

namespace QUITest\QUI\Utils\Text;

use QUI\Utils\Text\Word as Word;

/**
 * Class QUIUtilsTextWordTest
 */
class QUIUtilsTextWordTest extends \PHPUnit\Framework\TestCase
{
    public function testIsUseful()
    {
        $this->assertFalse(Word::isUseful(0));
        $this->assertFalse(Word::isUseful(''));
        $this->assertFalse(Word::isUseful('ab')); // blacklist / too short
        $this->assertFalse(Word::isUseful('haus')); // lowercase first char
        $this->assertFalse(Word::isUseful('Haus1')); // non letters
        $this->assertTrue(Word::isUseful('Nikolaus')); // valid useful word
    }

    public function testcountImportantWords()
    {
        $list = Word::countImportantWords('Dies ist das Haus vom Nikolaus Nikolaus');

        $this->assertEquals(2, count($list));
        $this->assertEquals(2, $list['Nikolaus']);
        $this->assertEquals(1, $list['Haus']);
    }
}
