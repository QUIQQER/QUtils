<?php

namespace QUITest\QUI;

use QUI\QDOM as QDOM;

/**
 * Class QDOMTest
 */
class QDOMTest extends \PHPUnit\Framework\TestCase
{
    public function testToString()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $this->expectException(\Error::class);
        (string)$Test;
    }

    public function testExistsAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $this->assertTrue($Test->existsAttribute('var1'), get_class($Test) . '->existsAttribute ');
        $this->assertTrue($Test->existsAttribute('var2'), get_class($Test) . '->existsAttribute');
    }

    public function testGetAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $this->assertSame(123, $Test->getAttribute('var1'), get_class($Test) . '->getAttribute var1');
        $this->assertSame(234, $Test->getAttribute('var2'), get_class($Test) . '->getAttribute var2');
    }

    public function testSetAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $this->assertSame(123, $Test->getAttribute('var1'), get_class($Test) . '->setAttribute var1');
    }

    public function testSetAttributes()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $this->assertSame(123, $Test->getAttribute('var1'), get_class($Test) . '->getAttribute var1');
        $this->assertSame(234, $Test->getAttribute('var2'), get_class($Test) . '->getAttribute var2');

        $this->expectException(\TypeError::class);
        $Test->setAttributes(false);
    }

    public function testSetAttributesWithNull(): void
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123
        ]);

        $Test->setAttributes(null);

        $this->assertSame(123, $Test->getAttribute('var1'));
    }

    public function testRemoveAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $Test->removeAttribute('var1');

        $this->assertFalse($Test->existsAttribute('var1'), get_class($Test) . '->removeAttribute var1');
        $this->assertFalse($Test->getAttribute('var1'), get_class($Test) . '->removeAttribute var1');
        $this->assertNotFalse($Test->getAttribute('var2'), get_class($Test) . '->removeAttribute var2');
    }

    public function testGetAttributes()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $attributes = $Test->getAttributes();

        $this->assertSame(123, $attributes['var1'], get_class($Test) . '->getAttribute var1');
        $this->assertSame(234, $attributes['var2'], get_class($Test) . '->getAttribute var2');
    }

    public function testGetType()
    {
        $Test = new QDOM();
        $Test->setAttributes([
            'var1' => 123,
            'var2' => 234
        ]);

        $type = $Test->getType();

        $this->assertIsString($type, get_class($Test) . '->getType');
    }

    public function testIsInstanceOf(): void
    {
        $Test = new QDOM();

        $this->assertTrue($Test->isInstanceOf(QDOM::class));
        $this->assertFalse($Test->isInstanceOf(\stdClass::class));
    }
}
