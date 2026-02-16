<?php

namespace QUITest\QUI;

/**
 * Class ExceptionTest
 */
class ExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $Exception = new \QUI\Exception(
            'some exception',
            404
        );

        $this->assertSame('some exception', $Exception->getMessage(), '\QUI\Exception->getMessage not working');
        $this->assertSame(404, $Exception->getCode(), '\QUI\Exception->getCode not working');
        $this->assertSame('QUI\Exception', $Exception->getType(), '\QUI\Exception->getType not working');

        $exception = $Exception->toArray();

        $this->assertArrayHasKey('code', $exception, '\QUI\Exception->toArray not working');
        $this->assertArrayHasKey('message', $exception, '\QUI\Exception->toArray not working');


        $Exception->setAttribute('test', 123);
        $Exception->setAttributes([
            'attr1' => 1,
            'attr2' => 2,
            'att31' => 3
        ]);

        $this->assertSame(123, $Exception->getAttribute('test'), '\QUI\Exception->setAttribute or getAttribute not working');
        $this->assertFalse($Exception->getAttribute('test1'), '\QUI\Exception->getAttribute not working');
        $this->assertSame(1, $Exception->getAttribute('attr1'), '\QUI\Exception->setAttributes not working');
    }

    public function testSetAttributesInvalidType()
    {
        $Exception = new \QUI\Exception('some exception', 404);

        $this->expectException(\TypeError::class);
        $Exception->setAttributes('lalalalala');
    }
}
