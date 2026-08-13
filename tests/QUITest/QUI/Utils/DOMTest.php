<?php

namespace QUITest\QUI\Utils;

use DOMDocument;
use DOMElement;
use QUI\QDOM;
use QUI\Utils\DOM;

class DOMTest extends \PHPUnit\Framework\TestCase
{
    protected function loadXml(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        return $dom;
    }

    public function testArrayToQDOM(): void
    {
        $qdom = DOM::arrayToQDOM(['a' => 1, 'b' => 'x']);

        $this->assertInstanceOf(QDOM::class, $qdom);
        $this->assertSame(1, $qdom->getAttribute('a'));
        $this->assertSame('x', $qdom->getAttribute('b'));
    }

    public function testAddTabsToToolbarMapsTabAttributesAndEvents(): void
    {
        $dom = $this->loadXml(
            '<root>' .
            '<tab name="general" custom="yes">' .
            '<image>icon.png</image><text>General</text><category/>' .
            '<onload require="load/module">load</onload>' .
            '<onunload require="unload/module">unload</onunload>' .
            '<template>template.html</template>' .
            '</tab>' .
            '<tab name="editor" type="wysiwyg"><text>Editor</text></tab>' .
            '</root>'
        );
        $Toolbar = new \QUI\Controls\Toolbar\Bar(['name' => 'test']);

        DOM::addTabsToToolbar($dom->getElementsByTagName('tab'), $Toolbar, 'vendor/package');

        $this->assertCount(2, $Toolbar->getChildren());
        $General = $Toolbar->getElementByName('general');
        $this->assertNotFalse($General);
        $this->assertSame('General', $General->getAttribute('text'));
        $this->assertSame('icon.png', $General->getAttribute('image'));
        $this->assertSame('vendor/package', $General->getAttribute('plugin'));
        $this->assertSame('xml', $General->getAttribute('type'));
        $this->assertSame('yes', $General->getAttribute('custom'));
        $this->assertSame('load', $General->getAttribute('onload'));
        $this->assertSame('load/module', $General->getAttribute('onload_require'));
        $this->assertSame('unload', $General->getAttribute('onunload'));
        $this->assertSame('unload/module', $General->getAttribute('onunload_require'));
        $this->assertSame('template.html', $General->getAttribute('template'));

        $Editor = $Toolbar->getElementByName('editor');
        $this->assertNotFalse($Editor);
        $this->assertTrue($Editor->getAttribute('wysiwyg'));
    }

    public function testDbHelperMethods(): void
    {
        $dom = $this->loadXml(
            '<root>' .
            '<field type="int" length="11">id</field>' .
            '<primary>id</primary>' .
            '<unique>slug</unique>' .
            '<index>idx_slug</index>' .
            '<auto_increment>id</auto_increment>' .
            '<foreign-key name="fk_user" foreignTable="users" foreignColumns="uuid" onDelete="CASCADE">userUuid</foreign-key>' .
            '<fulltext>title</fulltext>' .
            '</root>'
        );

        $this->assertSame(['id' => 'int(11) NOT NULL'], DOM::dbFieldDomToArray($dom->getElementsByTagName('field')->item(0)));
        $this->assertSame(['primary' => ['id']], DOM::dbPrimaryDomToArray($dom->getElementsByTagName('primary')->item(0)));
        $this->assertSame(['unique' => ['slug']], DOM::dbUniqueDomToArray($dom->getElementsByTagName('unique')->item(0)));
        $this->assertSame(['index' => ['idx_slug']], DOM::dbIndexDomToArray($dom->getElementsByTagName('index')->item(0)));
        $this->assertSame(['auto_increment' => 'id'], DOM::dbAutoIncrementDomToArray($dom->getElementsByTagName('auto_increment')->item(0)));
        $this->assertSame([
            'foreign-key' => [[
                'localColumns' => 'userUuid',
                'foreignTable' => 'users',
                'foreignColumns' => 'uuid',
                'name' => 'fk_user',
                'onDelete' => 'CASCADE'
            ]]
        ], DOM::dbForeignKeyDomToArray($dom->getElementsByTagName('foreign-key')->item(0)));
        $this->assertSame(['fulltext' => 'title'], DOM::dbAutoFullextDomToArray($dom->getElementsByTagName('fulltext')->item(0)));
    }

    public function testDbTableDomToArray(): void
    {
        $dom = $this->loadXml(
            '<root>' .
            '<table name="tbl" engine="InnoDB" no-site-reference="1" no-project-lang="1" no-auto-update="1" site-types="a,b">' .
            '<comment>my table</comment>' .
            '<field type="int" length="11">id</field>' .
            '<primary>id</primary>' .
            '<unique>slug</unique>' .
            '<index>idx_slug</index>' .
            '<auto_increment>id</auto_increment>' .
            '<foreign-key name="fk_user" foreignTable="users" foreignColumns="uuid">userUuid</foreign-key>' .
            '<fulltext>title</fulltext>' .
            '</table>' .
            '</root>'
        );

        $result = DOM::dbTableDomToArray($dom->getElementsByTagName('table')->item(0));

        $this->assertSame('tbl', $result['suffix']);
        $this->assertSame('InnoDB', $result['engine']);
        $this->assertTrue($result['no-site-reference']);
        $this->assertTrue($result['no-project-lang']);
        $this->assertTrue($result['no-auto-update']);
        $this->assertSame(['a', 'b'], $result['site-types']);
        $this->assertSame('my table', $result['comment']);
        $this->assertSame(['id' => 'int(11) NOT NULL'], $result['fields']);
        $this->assertSame(['id'], $result['primary']);
        $this->assertSame(['slug'], $result['unique']);
        $this->assertContains('idx_slug', $result['index']);
        $this->assertSame('id', $result['auto_increment']);
        $this->assertSame([
            [
                'localColumns' => 'userUuid',
                'foreignTable' => 'users',
                'foreignColumns' => 'uuid',
                'name' => 'fk_user'
            ]
        ], $result['foreign-key']);
        $this->assertSame('title', $result['fulltext']);
    }

    public function testGetTabs(): void
    {
        $dom = $this->loadXml('<window><tab name="a"/><tab name="b"/></window>');
        $tabs = DOM::getTabs($dom->documentElement);

        $this->assertCount(2, $tabs);
        $this->assertSame('a', $tabs[0]->getAttribute('name'));
        $this->assertSame('b', $tabs[1]->getAttribute('name'));
    }

    public function testGetTextFromNode(): void
    {
        $dom = $this->loadXml('<text>  Hello  </text>');
        $this->assertSame('Hello', DOM::getTextFromNode($dom->documentElement));

        $domLocale = $this->loadXml('<text><locale group="my.group" var="my.var"/></text>');
        $this->assertSame(['my.group', 'my.var'], DOM::getTextFromNode($domLocale->documentElement, false));
    }

    public function testGetWysiwygStyles(): void
    {
        $dom = $this->loadXml(
            '<root>' .
            '<wysiwyg><styles>' .
            '<style element="h1"><locale group="grp" var="headline"/><attribute name="class"> hero </attribute></style>' .
            '</styles></wysiwyg>' .
            '</root>'
        );

        $styles = DOM::getWysiwygStyles($dom, false);

        $this->assertCount(1, $styles);
        $this->assertSame(['grp', 'headline'], $styles[0]['text']);
        $this->assertSame('h1', $styles[0]['element']);
        $this->assertSame(['class' => 'hero'], $styles[0]['attributes']);
    }

    public function testGroupDomToString(): void
    {
        $dom = $this->loadXml(
            '<group conf="demo.group">' .
            '<text>Group Title</text>' .
            '<description>Group Description</description>' .
            '</group>'
        );

        $html = DOM::groupDomToString($dom->documentElement);

        $this->assertStringContainsString('btn-groups', $html);
        $this->assertStringContainsString('demo.group', $html);
        $this->assertStringContainsString('Group Title', $html);
        $this->assertStringContainsString('Group Description', $html);
    }

    public function testHtmlHelpers(): void
    {
        $innerBody = DOM::getInnerBodyFromHTML('<html><body><p>X</p></body></html>');
        $this->assertSame('<p>X</p>', $innerBody);

        $dom = $this->loadXml('<root><a>A</a><b>B</b></root>');
        $innerHtml = DOM::getInnerHTML($dom->documentElement);

        $this->assertStringContainsString('<a>A</a>', $innerHtml);
        $this->assertStringContainsString('<b>B</b>', $innerHtml);
    }

    public function testParseConfs(): void
    {
        $dom = $this->loadXml(
            '<section>' .
            '<conf name="c1"><type>int</type><defaultvalue>10</defaultvalue></conf>' .
            '<conf name="c2"></conf>' .
            '</section>'
        );

        $configs = DOM::parseConfs($dom->getElementsByTagName('conf'));

        $this->assertSame('int', $configs['c1']['type']);
        $this->assertSame('10', $configs['c1']['default']);
        $this->assertSame('string', $configs['c2']['type']);
        $this->assertSame('', $configs['c2']['default']);
    }

    public function testParseVarAndStringBuilders(): void
    {
        $this->assertSame('plain-text', DOM::parseVar('plain-text'));
        $this->assertSame(' ', DOM::parseVar(' '));

        $inputDom = $this->loadXml('<input conf="field1"><text>My Field</text></input>');
        $inputHtml = DOM::inputDomToString($inputDom->documentElement);
        $this->assertStringContainsString('name="field1"', $inputHtml);
        $this->assertStringContainsString('My Field', $inputHtml);

        $textAreaDom = $this->loadXml('<textarea conf="field2"><text>Text Area</text></textarea>');
        $textAreaHtml = DOM::textareaDomToString($textAreaDom->documentElement);
        $this->assertStringContainsString('name="field2"', $textAreaHtml);
        $this->assertStringContainsString('Text Area', $textAreaHtml);

        $selectDom = $this->loadXml(
            '<select conf="field3">' .
            '<text>Select Label</text>' .
            '<option value="1">One</option>' .
            '<option value="2">Two</option>' .
            '</select>'
        );
        $selectHtml = DOM::selectDomToString($selectDom->documentElement);
        $this->assertStringContainsString('name="field3"', $selectHtml);
        $this->assertStringContainsString('Select Label', $selectHtml);
        $this->assertStringContainsString('<option value="1">One</option>', $selectHtml);
        $this->assertStringContainsString('<option value="2">Two</option>', $selectHtml);
    }

    public function testSelectDomToStringRendersDescription(): void
    {
        $dom = $this->loadXml(
            '<select conf="field4">' .
            '<text>Select Label</text>' .
            '<description>Helpful hint</description>' .
            '<option value="1">One</option>' .
            '</select>'
        );
        $html = DOM::selectDomToString($dom->documentElement);

        $this->assertStringContainsString('<div class="description">Helpful hint</div>', $html);
        $this->assertStringNotContainsString('Helpful hint</option>', $html);
    }

    public function testButtonDomToString(): void
    {
        $dom = $this->loadXml('<button onclick="doIt()" image="icon.png"><text>Click</text></button>');
        $html = DOM::buttonDomToString($dom->documentElement);

        $this->assertStringContainsString('btn-button', $html);
        $this->assertStringContainsString('data-click="doIt()"', $html);
        $this->assertStringContainsString('data-image="icon.png"', $html);
        $this->assertStringContainsString('data-text="Click"', $html);
    }

    public function testDatabaseFieldVariants(): void
    {
        $dom = $this->loadXml(
            '<root>' .
            '<field>plain</field>' .
            '<field type="varchar" length="50" null="1">optional</field>' .
            '<field type="int" unsigned="true" default="0">amount</field>' .
            '</root>'
        );
        $fields = $dom->getElementsByTagName('field');

        $this->assertSame(['plain' => 'text NOT NULL'], DOM::dbFieldDomToArray($fields->item(0)));
        $this->assertSame(
            ['optional' => 'varchar(50) NULL'],
            DOM::dbFieldDomToArray($fields->item(1))
        );
        $this->assertSame(
            ['amount' => 'int NOT NULL'],
            DOM::dbFieldDomToArray($fields->item(2))
        );
    }

    public function testConfigParamsFromDom(): void
    {
        $dom = $this->loadXml(
            '<root><settings><config>' .
            '<section name="general">' .
            '<conf name="enabled"><type>bool</type><defaultvalue>1</defaultvalue></conf>' .
            '<custom>customValue</custom>' .
            '</section>' .
            '</config></settings></root>'
        );

        $result = DOM::getConfigParamsFromDOM($dom, true);
        $this->assertSame('bool', $result['general']['enabled']['type']);
        $this->assertSame('1', $result['general']['enabled']['default']);
        $this->assertSame(['type' => 'string', 'default' => ''], $result['general']['customValue']);

        $this->assertSame([], DOM::getConfigParamsFromDOM($this->loadXml('<root/>')));
        $this->assertSame([], DOM::getConfigParamsFromDOM($this->loadXml('<settings/>')));
    }

    public function testPanelAndPermissionParsing(): void
    {
        $panel = $this->loadXml(
            '<panel require="controls/panel"><title>Panel title</title><text>Panel text</text><image>icon.png</image></panel>'
        );
        $this->assertSame([
            'image' => 'icon.png',
            'title' => 'Panel title',
            'text' => 'Panel text',
            'require' => 'controls/panel'
        ], DOM::parsePanelToArray($panel->documentElement));
        $this->assertSame([], DOM::parsePanelToArray($this->loadXml('<root/>')->documentElement));

        $permission = $this->loadXml(
            '<permission name="package.action" type="bool" area="system">' .
            '<defaultvalue>0</defaultvalue><rootPermission>1</rootPermission>' .
            '<everyonePermission>0</everyonePermission><guestPermission>0</guestPermission>' .
            '</permission>'
        );
        $result = DOM::parsePermissionToArray($permission->documentElement);
        $this->assertSame('package.action', $result['name']);
        $this->assertSame('0', $result['defaultvalue']);
        $this->assertSame('1', $result['rootPermission']);
        $this->assertSame('0', $result['everyonePermission']);
        $this->assertSame('0', $result['guestPermission']);
    }

    public function testButtonsAndWindowParsing(): void
    {
        $dom = $this->loadXml(
            '<settings><title>Window title</title><window name="test-window">' .
            '<params><icon>icon.png</icon></params>' .
            '<categories><category name="save" require="controls/save" index="2">' .
            '<text>Save</text><title>Save title</title><onclick>save()</onclick><icon>save.png</icon>' .
            '</category></categories>' .
            '</window></settings>'
        );

        $buttons = DOM::getButtonsFromWindow($dom);
        $this->assertCount(1, $buttons);
        $this->assertSame('save', $buttons[0]->getAttribute('name'));
        $this->assertSame('Save', $buttons[0]->getAttribute('text'));
        $this->assertSame('save()', $buttons[0]->getAttribute('onclick'));

        $Window = DOM::parseDomToWindow($dom);
        $this->assertNotFalse($Window);
        $this->assertSame('test-window', $Window->getAttribute('name'));
        $this->assertSame('Window title', $Window->getAttribute('title'));
        $this->assertSame('icon.png', $Window->getAttribute('icon'));

        $this->assertFalse(DOM::parseDomToWindow($this->loadXml('<root/>')));
        $this->assertFalse(DOM::parseDomToWindow($this->loadXml('<settings/>')));
    }

    public function testCategoryRendersAllSupportedEntryTypes(): void
    {
        $dom = $this->loadXml(
            '<category name="general">' .
            '<title>General</title>' .
            '<input conf="username" type="text" class="wide" data-extra="value" placeholder="Name">' .
            '<text>User</text><description>User description</description></input>' .
            '<input conf="enabled" type="checkbox"><text>Enabled</text><description>Enable it</description></input>' .
            '<input conf="owner" type="user"><text>Owner</text></input>' .
            '<textarea conf="notes" data-mode="large"><text>Notes</text></textarea>' .
            '<select conf="choice"><text>Choice</text><description>Choose</description>' .
            '<option value="one">One</option></select>' .
            '<group conf="group"><text>Group</text><description>Group description</description></group>' .
            '<button onclick="run()" image="run.png"><text>Run</text></button>' .
            '<settings name="nested"><title>Nested</title>' .
            '<text>Introduction</text>' .
            '<input conf="nestedInput"><text>Nested input</text></input>' .
            '<textarea conf="nestedText"><text>Nested text</text></textarea>' .
            '<select conf="nestedSelect"><text>Nested select</text><option value="1">One</option></select>' .
            '<group conf="nestedGroup"><text>Nested group</text></group>' .
            '<button onclick="nested()"><text>Nested button</text></button>' .
            '</settings>' .
            '</category>'
        );

        $html = DOM::parseCategoryToHTML($dom->documentElement, 'en');

        $this->assertStringContainsString('data-name="general"', $html);
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('class="user field-container-field"', $html);
        $this->assertStringContainsString('name="notes"', $html);
        $this->assertStringContainsString('name="choice"', $html);
        $this->assertStringContainsString('btn-groups', $html);
        $this->assertStringContainsString('data-click="run()"', $html);
        $this->assertStringContainsString('data-name="nested"', $html);
        $this->assertStringContainsString('Nested input', $html);
    }

    public function testInvalidNodesReturnEmptyResults(): void
    {
        $root = $this->loadXml('<root/>')->documentElement;

        $this->assertSame('', DOM::buttonDomToString($root));
        $this->assertSame('', DOM::groupDomToString($root));
        $this->assertSame('', DOM::inputDomToString($root));
        $this->assertSame('', DOM::textareaDomToString($root));
        $this->assertSame('', DOM::selectDomToString($root));
        $this->assertSame([], DOM::getButtonsFromWindow($this->loadXml('<window/>')));
        $this->assertSame([], DOM::getWysiwygStyles($this->loadXml('<root/>')));
    }
}
