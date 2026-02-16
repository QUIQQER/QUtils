<?php

namespace QUITest\QUI\Utils\XML;

use DOMDocument;
use QUI\Utils\XML\DOMParser;

class DOMParserTest extends \PHPUnit\Framework\TestCase
{
    public function testInputDomToString(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<input conf="general.test" type="text"><text>Test</text></input>');

        $html = DOMParser::inputDomToString($dom->documentElement);

        $this->assertStringContainsString('<input type="text"', $html);
        $this->assertStringContainsString('name="general.test"', $html);
    }

    public function testTextareaDomToString(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<textarea conf="general.note"><text>Note</text></textarea>');

        $html = DOMParser::textareaDomToString($dom->documentElement);

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="general.note"', $html);
    }

    public function testSelectDomToString(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(
            '<select conf="general.mode"><text>Mode</text><option value="a">A</option><option value="b">B</option></select>'
        );

        $html = DOMParser::selectDomToString($dom->documentElement);

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('<option value="a">A</option>', $html);
    }

    public function testGroupDomToString(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<group conf="general.groups"><text>Groups</text></group>');

        $html = DOMParser::groupDomToString($dom->documentElement);

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('controls/usersAndGroups/Select', $html);
    }

    public function testButtonDomToString(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<button conf="general.save" type="button"><text>Save</text></button>');

        $html = DOMParser::buttonDomToString($dom->documentElement);

        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('Save', $html);
    }

    public function testGetAttributes(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML(
            '<input conf="general.value" class="my-class" label-style="width:10px;"><text>Value</text><description>Desc</description></input>'
        );

        $attributes = DOMParser::getAttributes($dom->documentElement);

        $this->assertSame('general.value', $attributes['conf']);
        $this->assertSame('Value', $attributes['text']);
        $this->assertSame('Desc', $attributes['desc']);
        $this->assertSame('width:10px;', $attributes['label-style']);
        $this->assertContains('my-class', $attributes['class']);
    }

    public function testCreateHtmlViaReflection(): void
    {
        $method = new \ReflectionMethod(DOMParser::class, 'createHTML');
        $method->setAccessible(true);

        $html = $method->invoke(
            null,
            '<input type="text" />',
            [
                'text' => 'Label',
                'desc' => '',
                'label' => true,
                'label-style' => ''
            ]
        );

        $this->assertStringContainsString('<label class="field-container"', $html);
    }

    public function testSmokeAllMethods(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML('<input conf="x.y"><text>X</text></input>');
        $input = $dom->documentElement;

        DOMParser::inputDomToString($input);
        DOMParser::textareaDomToString($input);
        DOMParser::selectDomToString($input);
        DOMParser::groupDomToString($input);
        DOMParser::buttonDomToString($input);
        DOMParser::getAttributes($input);

        $createHtml = new \ReflectionMethod(DOMParser::class, 'createHTML');
        $createHtml->setAccessible(true);
        $createHtml->invoke(
            null,
            '<input type="checkbox" />',
            ['text' => 'T', 'desc' => 'D', 'label' => false, 'label-style' => '']
        );

        $this->addToAssertionCount(1);
    }
}
