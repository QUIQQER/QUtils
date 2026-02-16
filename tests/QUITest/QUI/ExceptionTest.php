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

    public function testGetContextReturnsPassedContext(): void
    {
        $context = [
            'foo' => 'bar',
            'id' => 123
        ];

        $Exception = new \QUI\Exception('some exception', 500, $context);

        $this->assertSame($context, $Exception->getContext());
    }

    public function testConstructorWithArrayMessageWithoutLocaleKeys(): void
    {
        $Exception = new \QUI\Exception([0 => 'part1', 2 => 'part3'], 400);

        $this->assertSame('part1,part3', $Exception->getMessage());
        $this->assertSame(400, $Exception->getCode());
    }

    public function testToArrayContainsContext(): void
    {
        $context = ['scope' => 'unit-test'];
        $Exception = new \QUI\Exception('msg', 10, $context);

        $data = $Exception->toArray();

        $this->assertArrayHasKey('context', $data);
        $this->assertSame($context, $data['context']);
    }

    public function testAllPublicMethodsExplicitly(): void
    {
        $Exception = new \QUI\Exception('explicit', 99);

        $this->assertSame('QUI\Exception', $Exception->getType());
        $this->assertSame([], $Exception->getContext());

        $Exception->setAttribute('a', 'A');
        $this->assertSame('A', $Exception->getAttribute('a'));
        $this->assertFalse($Exception->getAttribute('missing'));

        $Exception->setAttributes([
            'b' => 'B',
            'c' => 'C'
        ]);
        $this->assertSame('B', $Exception->getAttribute('b'));
        $this->assertSame('C', $Exception->getAttribute('c'));

        $array = $Exception->toArray();
        $this->assertSame(99, $array['code']);
        $this->assertSame('explicit', $array['message']);
        $this->assertSame('QUI\Exception', $array['type']);
    }
}
