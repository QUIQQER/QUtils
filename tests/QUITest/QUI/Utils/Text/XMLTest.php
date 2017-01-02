<?php

namespace QUITest\QUI\Utils\Text;

use QUI\Utils\Text\XML;

/**
 * Class QUIUtilsTextWordTest
 * @package QUITest\QUI\Utils\WordTest
 */
class XMLTest extends \PHPUnit_Framework_TestCase
{
    public function testParseCategoriesToCollection()
    {
        // collection test
        $Collection = XML::parseCategoriesToCollection(
            dirname(__FILE__) . '/settings.xml'
        );

        $this->assertGreaterThan(1, $Collection->size());


        // html test
        $html = XML::getCategoriesHtml(dirname(__FILE__) . '/settings.xml');

        $this->assertStringStartsWith('<table', $html);
        $this->assertStringEndsWith('</table>', $html);
    }
}
