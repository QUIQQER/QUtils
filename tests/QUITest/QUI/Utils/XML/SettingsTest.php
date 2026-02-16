<?php

namespace QUITest\QUI\Utils\XML;

use DOMDocument;
use QUI\Utils\XML\Settings;

class SettingsTest extends \PHPUnit\Framework\TestCase
{
    protected function createSettings(): Settings
    {
        $Settings = new Settings();
        $Settings->setXMLPath('//settings/window');

        return $Settings;
    }

    public function testGetInstanceReturnsSingleton(): void
    {
        $this->assertSame(Settings::getInstance(), Settings::getInstance());
    }

    public function testSetXMLPathAndGetPanel(): void
    {
        $Settings = $this->createSettings();
        $panel = $Settings->getPanel(dirname(__DIR__) . '/XML/settings.xml');

        $this->assertSame('My Settings', $panel['title']);
        $this->assertNotEmpty($panel['icon']);
        $this->assertArrayHasKey('categories', $panel);
    }

    public function testGetCategoriesAndGetCategoriesHtml(): void
    {
        $Settings = $this->createSettings();

        $Collection = $Settings->getCategories(dirname(__DIR__) . '/XML/settings.xml');
        $this->assertGreaterThan(1, $Collection->size());

        $html = $Settings->getCategoriesHtml(dirname(__DIR__) . '/XML/settings.xml');
        $this->assertStringStartsWith('<table', $html);
        $this->assertStringEndsWith('</table>', $html);
    }

    public function testParseCategory(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(
            '<category name="cat" index="2" require="mod/cat" click="onClick">' .
            '<title>Title</title>' .
            '<icon>icon.png</icon>' .
            '<settings name="s1" index="1"><title>S1</title><text>plain</text></settings>' .
            '</category>'
        );

        $Settings = $this->createSettings();
        $result = $Settings->parseCategory($dom->documentElement);

        $this->assertSame('cat', $result['name']);
        $this->assertSame('Title', $result['title']);
        $this->assertSame('icon.png', $result['icon']);
        $this->assertGreaterThan(0, $result['items']->size());
    }

    public function testParseSettingsWithAllFieldTypes(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(
            '<settings name="my.setting" index="1" icon="settings">' .
            '<title>Section Title</title>' .
            '<input conf="a.b"><text>Input</text></input>' .
            '<select conf="a.c"><text>Select</text><option value="1">One</option></select>' .
            '<textarea conf="a.d"><text>Area</text></textarea>' .
            '<group conf="a.e"><text>Group</text></group>' .
            '<button conf="a.f" type="button"><text>Run</text></button>' .
            '<text row-style="background:#fff;">Inline</text>' .
            '</settings>'
        );

        $Settings = $this->createSettings();
        $result = $Settings->parseSettings($dom->documentElement);

        $this->assertSame('my.setting', $result['name']);
        $this->assertSame('Section Title', $result['title']);
        $this->assertNotEmpty($result['items']);
    }
}
