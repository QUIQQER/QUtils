<?php

namespace QUITest\QUI\Utils\Text;

use QUI\Utils\XML\Settings;

/**
 * Class QUIUtilsTextWordTest
 * @package QUITest\QUI\Utils\WordTest
 */
class SettingsTest extends \PHPUnit_Framework_TestCase
{
    public function testParseCategoriesToCollection()
    {
        $Settings = Settings::getInstance();

        // collection test
        $Collection = $Settings->parseCategoriesToCollection(
            dirname(__FILE__) . '/settings.xml'
        );

        $this->assertGreaterThan(1, $Collection->size());


        // html test
        $html = $Settings->getCategoriesHtml(dirname(__FILE__) . '/settings.xml');

        $this->assertStringStartsWith('<table', $html);
        $this->assertStringEndsWith('</table>', $html);
    }
}
