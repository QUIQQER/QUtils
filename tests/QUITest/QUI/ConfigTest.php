<?php

namespace QUITest\QUI;

use QUI\Config as Config;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 */
class ConfigTest extends TestCase
{
    public function getConfig()
    {
        return new Config(
            dirname(__FILE__) . '/ConfigTest.ini'
        );
    }

    public function testToArray()
    {
        $Config = $this->getConfig();
        $array = $Config->toArray();

        $this->assertEquals(1, $array['section1']['var1']);
        $this->assertEquals(1, $array['section1']['var2']);

        $this->assertEquals(3, $array['section2']['var3']);
    }

    public function testToJSON()
    {
        $Config = $this->getConfig();
        $json = $Config->toJSON();
        $array = json_decode($json, true);

        $this->assertEquals(1, $array['section1']['var1']);
        $this->assertEquals(1, $array['section1']['var2']);

        $this->assertEquals(3, $array['section2']['var3']);
    }

    public function testGetSection()
    {
        $Config = $this->getConfig();
        $section = $Config->getSection('section2');

        $this->assertEquals(3, $section['var3']);

        $this->assertFalse($Config->getSection('unknown'));
    }

    public function testGetValue()
    {
        $Config = $this->getConfig();

        $this->assertEquals(1, $Config->getValue('section1', 'var1'));

        $this->assertFalse($Config->getValue('unknown', 'unknown'));
        $this->assertFalse($Config->getValue('section1', 'unknown'));
    }

    public function testGet()
    {
        $Config = $this->getConfig();

        $this->assertEquals(1, $Config->get('section1', 'var1'));

        $section = $Config->get('section2');
        $this->assertEquals(3, $section['var3']);
    }

    public function testGetFilename()
    {
        $Config = $this->getConfig();

        $this->assertEquals(
            dirname(__FILE__) . '/ConfigTest.ini.php',
            $Config->getFilename()
        );
    }

    public function testExistValue()
    {
        $Config = $this->getConfig();


        $this->assertTrue($Config->existValue('section1'));
        $this->assertFalse($Config->existValue('unknown'));

        $this->assertTrue($Config->existValue('section1', 'var1'));
        $this->assertFalse($Config->existValue('unknown', 'var1'));
    }

    public function testSet()
    {
        $Config = $this->getConfig();

        $this->assertTrue($Config->set('section3', 'val1', 'test'));
        $this->assertTrue($Config->existValue('section3'));

        $this->assertTrue($Config->set('section4', 'val1', 'test'));
        $this->assertEquals('test', $Config->get('section4', 'val1'));
    }

    public function testSetInvalidArgs()
    {
        $Config = $this->getConfig();

        $this->expectException(\TypeError::class);
        $Config->set();
    }

    public function testSetSection()
    {
        $Config = $this->getConfig();

        $this->assertTrue($Config->setSection(false, [
            'val1' => 'test'
        ]));

        $this->assertEquals('test', $Config->getValue(0, 'val1'));
    }

    public function testSetSectionInvalidArgs()
    {
        $Config = $this->getConfig();

        $this->expectException(\TypeError::class);
        $Config->setSection('section3', 'string');
    }

    public function testSetValue()
    {
        $Config = $this->getConfig();

        $this->assertEquals(
            true,
            $Config->setValue('my_section', null, 'string')
        );
        $this->assertEquals('string', $Config->getSection('my_section'));
    }

    public function testDel()
    {
        $Config = $this->getConfig();

        $this->assertTrue($Config->set('section3', 'val1', 'test'));
        $this->assertTrue($Config->existValue('section3'));

        $Config->del('section3', 'val1');
        $this->assertFalse($Config->existValue('section3', 'val1'));

        $Config->del('section3');
        $this->assertFalse($Config->existValue('section3'));
        $this->assertFalse($Config->existValue('section3', 'val1'));

        $Config->del('section3ss');
    }

    public function testSave()
    {
        $Config = $this->getConfig();

        $Config->set('section3', 'val1', 'test');
        $Config->save();


        $Config2 = $this->getConfig();
        $this->assertEquals('test', $Config2->get('section3', 'val1'));

        $Config2->del('section3');
        $Config2->save();

        $Config3 = $this->getConfig();
        $this->assertFalse($Config3->existValue('section3'));
    }
}
