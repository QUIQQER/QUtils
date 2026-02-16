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

    public function testDbHelperMethods(): void
    {
        $dom = $this->loadXml(
            '<root>' .
            '<field type="int" length="11">id</field>' .
            '<primary>id</primary>' .
            '<unique>slug</unique>' .
            '<index>idx_slug</index>' .
            '<auto_increment>id</auto_increment>' .
            '<fulltext>title</fulltext>' .
            '</root>'
        );

        $this->assertSame(['id' => 'int(11) NOT NULL'], DOM::dbFieldDomToArray($dom->getElementsByTagName('field')->item(0)));
        $this->assertSame(['primary' => ['id']], DOM::dbPrimaryDomToArray($dom->getElementsByTagName('primary')->item(0)));
        $this->assertSame(['unique' => ['slug']], DOM::dbUniqueDomToArray($dom->getElementsByTagName('unique')->item(0)));
        $this->assertSame(['index' => ['idx_slug']], DOM::dbIndexDomToArray($dom->getElementsByTagName('index')->item(0)));
        $this->assertSame(['auto_increment' => 'id'], DOM::dbAutoIncrementDomToArray($dom->getElementsByTagName('auto_increment')->item(0)));
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

    public function testButtonDomToString(): void
    {
        $dom = $this->loadXml('<button onclick="doIt()" image="icon.png"><text>Click</text></button>');
        $html = DOM::buttonDomToString($dom->documentElement);

        $this->assertStringContainsString('btn-button', $html);
        $this->assertStringContainsString('data-click="doIt()"', $html);
        $this->assertStringContainsString('data-image="icon.png"', $html);
        $this->assertStringContainsString('data-text="Click"', $html);
    }
}
