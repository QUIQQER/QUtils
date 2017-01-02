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
        $result = XML::parseCategoriesToCollection(
            dirname(__FILE__) . '/settingx.xml'
        );


        var_dump($result);
    }
}
