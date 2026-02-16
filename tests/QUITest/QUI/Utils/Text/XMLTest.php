<?php

namespace QUITest\QUI\Utils\Text;

use DOMDocument;
use DOMElement;
use QUI\Utils\Text\XML;

use function md5;
use function sys_get_temp_dir;
use function unlink;
use function uniqid;

class XMLTest extends \PHPUnit\Framework\TestCase
{
    protected array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->tempFiles = [];
    }

    protected function createTempXmlFile(string $xml): string
    {
        $file = sys_get_temp_dir() . '/qui-xml-test-' . uniqid('', true) . '.xml';
        file_put_contents($file, $xml);
        $this->tempFiles[] = $file;

        return $file;
    }

    public function testGetDomFromXmlInvalidAndMissingFile(): void
    {
        $this->assertNull(XML::getDomFromXml('/tmp/does-not-exist.xml')->documentElement);
        $this->assertNull(XML::getDomFromXml('/tmp/not-xml.txt')->documentElement);
    }

    public function testGetDomFromXmlValidFile(): void
    {
        $file = $this->createTempXmlFile('<quiqqer><a>1</a></quiqqer>');

        $dom = XML::getDomFromXml($file);

        $this->assertInstanceOf(DOMDocument::class, $dom);
        $this->assertInstanceOf(DOMElement::class, $dom->documentElement);
        $this->assertSame('quiqqer', $dom->documentElement->nodeName);
    }

    public function testXmlParsingMethodsWithMainFixture(): void
    {
        $file = $this->createTempXmlFile(
            '<quiqqer>' .
            '<console><tool exec="tool:run"/></console>' .
            '<wysiwyg>' .
            '<css src="a.css"/><css src="b.css"/>' .
            '<editors><editor name="ed1"/><editor name="ed2"/></editors>' .
            '</wysiwyg>' .
            '<database>' .
            '<global execute="init"><table name="tbl" engine="InnoDB"><field type="int">id</field><primary>id</primary></table></global>' .
            '<projects><table name="ptbl"><field type="text">title</field></table></projects>' .
            '</database>' .
            '<events><event on="x" fire="y"/></events>' .
            '<site>' .
            '<layouts><layout type="default"/><layout type="alt"/></layouts>' .
            '<types><type type="page"><event on="a" fire="b"/></type></types>' .
            '</site>' .
            '<menu><item name="one" parent="/"><text>One</text></item><item name="two" parent="/one"><text>Two</text></item></menu>' .
            '<package>' .
            '<title>Pkg Title</title><description>Desc</description><image src="img.png"/>' .
            '<template_parent>parent/template</template_parent>' .
            '<preview><image src="p1.png"/><image src="p2.png"/></preview>' .
            '<provider><js src="a.js"/><css src="a.css"/></provider>' .
            '</package>' .
            '<settings><window name="main"><categories><category name="cat1"/><category name="cat2"/></categories></window></settings>' .
            '<project><settings><window name="proj"/></settings></project>' .
            '<template_engines><engine name="twig"/><engine name="php"/></template_engines>' .
            '<widgets><widget><title>Inline</title></widget></widgets>' .
            '</quiqqer>'
        );

        $this->assertSame(['tool:run'], XML::getConsoleToolsFromXml($file));
        $this->assertSame(['a.css', 'b.css'], XML::getWysiwygCSSFromXml($file));

        $db = XML::getDataBaseFromXml($file);
        $this->assertArrayHasKey('globals', $db);
        $this->assertArrayHasKey('projects', $db);
        $this->assertNotEmpty($db['globals']);

        $events = XML::getEventsFromXml($file);
        $this->assertCount(1, $events);
        $this->assertSame('event', $events[0]->nodeName);

        $layouts = XML::getLayoutsFromXml($file);
        $this->assertCount(2, $layouts);
        $this->assertSame('default', XML::getLayoutFromXml($file, 'default')->getAttribute('type'));
        $this->assertFalse(XML::getLayoutFromXml($file, 'missing-layout'));

        $menuItems = XML::getMenuItemsXml($file);
        $this->assertCount(2, $menuItems);
        $this->assertSame('one', $menuItems[0]->getAttribute('name'));

        $package = XML::getPackageFromXMLFile($file);
        $this->assertSame('Pkg Title', $package['title']);
        $this->assertSame('Desc', $package['description']);
        $this->assertSame('img.png', $package['image']);
        $this->assertCount(2, $package['preview']);
        $this->assertSame(['a.js'], $package['provider']['js']);
        $this->assertSame(['a.css'], $package['provider']['css']);

        $categories = XML::getSettingCategoriesFromXml($file);
        $this->assertCount(2, $categories);
        $this->assertSame('cat2', $categories[1]->getAttribute('name'));

        $category = XML::getSettingCategoryFromXml($file, 'cat1');
        $this->assertInstanceOf(DOMElement::class, $category);
        $this->assertFalse(XML::getSettingCategoryFromXml($file, 'missing-cat'));

        $settingWindows = XML::getSettingWindowsFromXml($file);
        $this->assertCount(1, $settingWindows);

        $projectWindows = XML::getProjectSettingWindowsFromXml($file);
        $this->assertCount(1, $projectWindows);

        $types = XML::getTypesFromXml($file);
        $this->assertCount(1, $types);
        $this->assertSame('page', $types[0]->getAttribute('type'));

        $engines = XML::getTemplateEnginesFromXml($file);
        $this->assertCount(2, $engines);

        $editors = XML::getWysiwygEditorsFromXml($file);
        $this->assertCount(2, $editors);

        $widgets = XML::getWidgetsFromXml($file);
        $this->assertCount(1, $widgets);
        $this->assertSame(md5($file . '0'), $widgets[0]->getAttribute('name'));

        $tabs = XML::getTabsFromXml($file);
        $this->assertSame([], $tabs);

        $dom = XML::getDomFromXml($file);
        $this->assertSame([], XML::getTabsFromDom($dom));
        $this->assertSame([], XML::getSiteTabsFromDom($dom));
    }

    public function testGetWidgetFromXmlAndWidgetsWithSrc(): void
    {
        $widgetSourceFile = $this->createTempXmlFile('<widget><title>External</title></widget>');
        $widgetsFile = $this->createTempXmlFile(
            '<quiqqer><widgets><widget src="' . $widgetSourceFile . '"/></widgets></quiqqer>'
        );

        $widget = XML::getWidgetFromXml($widgetSourceFile);
        $this->assertInstanceOf(DOMElement::class, $widget);
        $this->assertSame(md5($widgetSourceFile), $widget->getAttribute('name'));

        $widgets = XML::getWidgetsFromXml($widgetsFile);
        $this->assertCount(1, $widgets);
        $this->assertSame(md5($widgetSourceFile), $widgets[0]->getAttribute('name'));
    }

    public function testGetLocaleGroupsFromDom(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(
            '<quiqqer>' .
            '<locales>' .
            '<groups name="my.group" datatype="js">' .
            '<locale name="title" html="1" priority="2"><de>Titel</de><en>Title</en></locale>' .
            '</groups>' .
            '</locales>' .
            '</quiqqer>'
        );

        $groups = XML::getLocaleGroupsFromDom($dom);

        $this->assertCount(1, $groups);
        $this->assertSame('my.group', $groups[0]['group']);
        $this->assertSame('js', $groups[0]['datatype']);
        $this->assertCount(1, $groups[0]['locales']);
        $this->assertSame('title', $groups[0]['locales'][0]['name']);
        $this->assertSame('Titel', $groups[0]['locales'][0]['de']);
        $this->assertSame('Title', $groups[0]['locales'][0]['en']);
    }
}
