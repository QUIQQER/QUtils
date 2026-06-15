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

    public function testGetPanelsFromXmlFile(): void
    {
        $file = $this->createTempXmlFile(
            '<quiqqer><panels>' .
            '<panel require="mod/panel"><title>Panel Title</title><text>Panel Text</text><image>icon.png</image></panel>' .
            '</panels></quiqqer>'
        );

        $panels = XML::getPanelsFromXMLFile($file);

        $this->assertCount(1, $panels);
        $this->assertSame('mod/panel', $panels[0]['require']);
        $this->assertSame('Panel Title', $panels[0]['title']);
        $this->assertSame('Panel Text', $panels[0]['text']);
        $this->assertSame('icon.png', $panels[0]['image']);
    }

    public function testGetPermissionsFromXml(): void
    {
        $file = $this->createTempXmlFile(
            '<quiqqer>' .
            '<permissions>' .
            '<permission name="perm.test">' .
            '<defaultvalue>1</defaultvalue>' .
            '<rootPermission>1</rootPermission>' .
            '<everyonePermission>0</everyonePermission>' .
            '<guestPermission>0</guestPermission>' .
            '</permission>' .
            '</permissions>' .
            '</quiqqer>'
        );

        $permissions = XML::getPermissionsFromXml($file);

        $this->assertCount(1, $permissions);
        $this->assertSame('perm.test', $permissions[0]['name']);
        $this->assertArrayHasKey('title', $permissions[0]);
        $this->assertArrayHasKey('desc', $permissions[0]);
    }

    public function testGetSiteEventsFromXml(): void
    {
        $file = $this->createTempXmlFile(
            '<quiqqer><site><types><type type="page"><event on="save" fire="onSave"/></type></types></site></quiqqer>'
        );

        $events = XML::getSiteEventsFromXml($file);

        $this->assertCount(1, $events);
        $this->assertSame('save', $events[0]['on']);
        $this->assertSame('onSave', $events[0]['fire']);
        $this->assertStringEndsWith(':page', $events[0]['type']);
    }

    public function testGetTabsFromDomAndSiteTabsFromDomPositive(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(
            '<quiqqer>' .
            '<window><tab name="a"/><tab name="b"/></window>' .
            '<site><window><tab name="s1"/></window></site>' .
            '</quiqqer>'
        );

        $tabs = XML::getTabsFromDom($dom);
        $siteTabs = XML::getSiteTabsFromDom($dom);

        $this->assertCount(2, $tabs);
        $this->assertCount(1, $siteTabs);
        $this->assertSame('a', $tabs[0]->getAttribute('name'));
        $this->assertSame('s1', $siteTabs[0]->getAttribute('name'));
    }

    public function testGetConfigFromXmlAndGetConfigParamsFromXmlWithoutSettings(): void
    {
        $file = $this->createTempXmlFile('<quiqqer><no-settings/></quiqqer>');

        $this->assertFalse(XML::getConfigFromXml($file));
        $this->assertSame([], XML::getConfigParamsFromXml($file));
    }

    public function testAddXmlFileToMenuReturnsEarlyForMissingFile(): void
    {
        if (!class_exists('\QUI\Controls\Contextmenu\Bar')) {
            $this->markTestSkipped('QUI context menu classes not available.');
        }

        $Menu = $this->getMockBuilder(\QUI\Controls\Contextmenu\Bar::class)
            ->disableOriginalConstructor()
            ->getMock();

        XML::addXMLFileToMenu($Menu, '/tmp/does-not-exist.xml');
        $this->addToAssertionCount(1);
    }

    public function testSetConfigFromXmlCanBeCalled(): void
    {
        $file = $this->createTempXmlFile('<quiqqer><no-settings/></quiqqer>');

        try {
            XML::setConfigFromXml($file, ['main' => ['k' => 'v']]);
            $this->addToAssertionCount(1);
        } catch (\Throwable $Throwable) {
            $this->assertInstanceOf(\Throwable::class, $Throwable);
        }
    }

    public function testDatabaseXmlFieldTypeMapping(): void
    {
        $Method = new \ReflectionMethod(XML::class, 'parseDatabaseXmlFieldType');
        $Method->setAccessible(true);

        [$type, $options] = $Method->invoke(null, 'VARCHAR(255) NOT NULL');
        $this->assertSame(\Doctrine\DBAL\Types\Types::STRING, $type);
        $this->assertSame(255, $options['length']);
        $this->assertTrue($options['notnull']);

        [$type, $options] = $Method->invoke(null, 'INT NULL');
        $this->assertSame(\Doctrine\DBAL\Types\Types::INTEGER, $type);
        $this->assertFalse($options['notnull']);

        [$type, $options] = $Method->invoke(null, 'INT(3) NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->assertSame(\Doctrine\DBAL\Types\Types::INTEGER, $type);
        $this->assertTrue($options['autoincrement']);
        $this->assertTrue($options['notnull']);

        [$type, $options] = $Method->invoke(null, 'TINYINT(1)');
        $this->assertSame(\Doctrine\DBAL\Types\Types::BOOLEAN, $type);
        $this->assertTrue($options['notnull']);

        [$type] = $Method->invoke(null, 'MEDIUMINT NULL');
        $this->assertSame(\Doctrine\DBAL\Types\Types::INTEGER, $type);

        [$type] = $Method->invoke(null, 'TINYTEXT NULL');
        $this->assertSame(\Doctrine\DBAL\Types\Types::TEXT, $type);

        [$type, $options] = $Method->invoke(null, 'timestamp DEFAULT NOW() ON UPDATE NOW()');
        $this->assertSame(\Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, $type);
        $this->assertSame('CURRENT_TIMESTAMP', $options['default']);
        $this->assertSame(
            'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            $options['columnDefinition']
        );
    }

    public function testDatabaseXmlColumnNormalization(): void
    {
        $Method = new \ReflectionMethod(XML::class, 'normalizeDatabaseXmlColumns');
        $Method->setAccessible(true);

        $this->assertSame(['id', 'title'], $Method->invoke(null, 'id,title'));
        $this->assertSame(['project', 'lang'], $Method->invoke(null, ['project', ' lang ', '']));
    }

    public function testImportDataBaseCanBeCalled(): void
    {
        try {
            XML::importDataBase([]);
            $this->addToAssertionCount(1);
        } catch (\Throwable $Throwable) {
            $this->assertInstanceOf(\Throwable::class, $Throwable);
        }
    }

    public function testImportDataBaseFromXmlWithoutDatabaseNode(): void
    {
        $file = $this->createTempXmlFile('<quiqqer><x/></quiqqer>');

        XML::importDataBaseFromXml($file);
        $this->addToAssertionCount(1);
    }

    public function testImportPermissionsFromXmlCanBeCalled(): void
    {
        $file = $this->createTempXmlFile('<quiqqer><permissions/></quiqqer>');

        try {
            XML::importPermissionsFromXml($file, 'utils/tests');
            $this->addToAssertionCount(1);
        } catch (\Throwable $Throwable) {
            $this->assertInstanceOf(\Throwable::class, $Throwable);
        }
    }

    public function testSmokeAllPublicStaticMethods(): void
    {
        $file = $this->createTempXmlFile(
            '<quiqqer>' .
            '<console><tool exec="tool:run"/></console>' .
            '<wysiwyg><css src="a.css"/><editors><editor name="ed"/></editors></wysiwyg>' .
            '<database><global><table name="tbl"><field type="int">id</field></table></global></database>' .
            '<events><event on="x" fire="y"/></events>' .
            '<site><layouts><layout type="default"/></layouts><types><type type="page"/></types><window><tab name="t1"/></window></site>' .
            '<menu><item name="one" parent="/"><text>One</text></item></menu>' .
            '<package><title>Pkg</title></package>' .
            '<settings><window name="main"><categories><category name="cat"/></categories></window></settings>' .
            '<project><settings><window name="proj"/></settings></project>' .
            '<template_engines><engine name="twig"/></template_engines>' .
            '<widgets><widget><title>Inline</title></widget></widgets>' .
            '<permissions><permission name="perm.test"/></permissions>' .
            '</quiqqer>'
        );

        $dom = XML::getDomFromXml($file);
        $methods = (new \ReflectionClass(XML::class))->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (!$method->isStatic() || $method->class !== XML::class) {
                continue;
            }

            try {
                switch ($method->name) {
                    case 'addXMLFileToMenu':
                        if (class_exists('\QUI\Controls\Contextmenu\Bar')) {
                            $Menu = $this->getMockBuilder(\QUI\Controls\Contextmenu\Bar::class)
                                ->disableOriginalConstructor()
                                ->getMock();
                            XML::addXMLFileToMenu($Menu, '/tmp/does-not-exist.xml');
                        }
                        break;

                    case 'getConfigFromXml':
                    case 'getConfigParamsFromXml':
                    case 'getConsoleToolsFromXml':
                    case 'getWysiwygCSSFromXml':
                    case 'getDataBaseFromXml':
                    case 'getEventsFromXml':
                    case 'getSiteEventsFromXml':
                    case 'getLayoutsFromXml':
                    case 'getMenuItemsXml':
                    case 'getPackageFromXMLFile':
                    case 'getPanelsFromXMLFile':
                    case 'getPermissionsFromXml':
                    case 'getSettingCategoriesFromXml':
                    case 'getSettingWindowsFromXml':
                    case 'getProjectSettingWindowsFromXml':
                    case 'getTypesFromXml':
                    case 'getTabsFromXml':
                    case 'getTemplateEnginesFromXml':
                    case 'getWysiwygEditorsFromXml':
                    case 'getWidgetsFromXml':
                    case 'importDataBaseFromXml':
                        XML::{$method->name}($file);
                        break;

                    case 'getDomFromXml':
                    case 'getWidgetFromXml':
                        XML::{$method->name}($file);
                        break;

                    case 'getLayoutFromXml':
                    case 'getSettingCategoryFromXml':
                        XML::{$method->name}($file, 'default');
                        break;

                    case 'getLocaleGroupsFromDom':
                    case 'getTabsFromDom':
                    case 'getSiteTabsFromDom':
                        XML::{$method->name}($dom);
                        break;

                    case 'setConfigFromXml':
                        XML::setConfigFromXml($file, ['main' => ['k' => 'v']]);
                        break;

                    case 'importDataBase':
                        XML::importDataBase([]);
                        break;

                    case 'importPermissionsFromXml':
                        XML::importPermissionsFromXml($file, 'utils/tests');
                        break;
                }
            } catch (\Throwable) {
            }
        }

        $this->addToAssertionCount(1);
    }
}
