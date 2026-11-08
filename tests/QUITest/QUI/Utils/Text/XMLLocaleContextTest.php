<?php

namespace QUITest\QUI\Utils\Text;

use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QUI\Utils\Text\XML;

class XMLLocaleContextTest extends TestCase
{
    public function testParsesContextsWithoutChangingLegacyMetadataOrTranslations(): void
    {
        $Dom = new DOMDocument();
        $Dom->loadXML(
            '<quiqqer><locales><groups name="vendor/package" datatype="php,js">' .
            '<locale name="title" html="1" priority="2" context=" namescoreDashboard, frontend, frontend ">' .
            '<de>Titel</de><en>Title</en></locale></groups></locales></quiqqer>'
        );

        $groups = XML::getLocaleGroupsFromDom($Dom);

        $this->assertCount(1, $groups);
        $this->assertSame('vendor/package', $groups[0]['group']);
        $this->assertSame('php,js', $groups[0]['datatype']);
        $this->assertCount(1, $groups[0]['locales']);
        $locale = $groups[0]['locales'][0];

        $this->assertSame('title', $locale['name']);
        $this->assertSame(true, $locale['html']);
        $this->assertSame(2, $locale['priority']);
        $this->assertSame('frontend,namescoreDashboard', $locale['context']);
        $this->assertSame('Titel', $locale['de']);
        $this->assertSame('Title', $locale['en']);
    }

    public function testDistinguishesAbsentAndExplicitlyEmptyContextWithoutGroupInheritance(): void
    {
        $Dom = new DOMDocument();
        $Dom->loadXML(
            '<locales><groups name="vendor/package" datatype="js" context="administration">' .
            '<locale name="legacy"><en>Legacy</en></locale>' .
            '<locale name="empty" context=""><en>Empty</en></locale>' .
            '<locale name="whitespace" context="   "><en>Whitespace</en></locale>' .
            '</groups></locales>'
        );
        $locales = XML::getLocaleGroupsFromDom($Dom)[0]['locales'];

        $this->assertArrayNotHasKey('context', $locales[0]);
        $this->assertSame('', $locales[1]['context']);
        $this->assertSame('', $locales[2]['context']);
    }

    public function testRejectsInvalidContextBeforeReturningParsedGroups(): void
    {
        $Dom = new DOMDocument();
        $Dom->loadXML(
            '<locales><groups name="vendor/package">' .
            '<locale name="title" context="../escape"><en>Title</en></locale>' .
            '</groups></locales>'
        );

        $this->expectException(InvalidArgumentException::class);
        XML::getLocaleGroupsFromDom($Dom);
    }

    public function testPreservesPackageOwnershipFromExportedOverride(): void
    {
        $Dom = new DOMDocument();
        $Dom->load(dirname(__DIR__, 4) . '/Fixtures/LocaleContext/exported-override.xml');
        $locales = XML::getLocaleGroupsFromDom($Dom)[0]['locales'];

        $this->assertSame('vendor/package', $locales[0]['package']);
        $this->assertSame('administration', $locales[0]['context']);
        $this->assertSame('vendor/theme', $locales[1]['package']);
        $this->assertArrayNotHasKey('context', $locales[1]);
        $this->assertSame(10, $locales[1]['priority']);
        $this->assertSame('Individueller Titel', $locales[1]['de']);
    }

    public function testDoesNotInheritPackageMetadataFromGroups(): void
    {
        $Dom = new DOMDocument();
        $Dom->loadXML(
            '<locales><groups name="vendor/package" package="vendor/theme">' .
            '<locale name="legacy"><en>Legacy</en></locale>' .
            '<locale name="empty" package=""><en>Empty</en></locale>' .
            '</groups></locales>'
        );
        $locales = XML::getLocaleGroupsFromDom($Dom)[0]['locales'];

        $this->assertArrayNotHasKey('package', $locales[0]);
        $this->assertSame('', $locales[1]['package']);
    }
}
