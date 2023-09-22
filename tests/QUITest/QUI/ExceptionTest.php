<?php

namespace QUITest\QUI;

/**
 * Class ExceptionTest
 * @package QUITest\QUI
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $Exception = new \QUI\Exception(
            'some exception',
            404
        );

        if ($Exception->getMessage() != 'some exception') {
            $this->fail('\QUI\Exception->getMessage not working');
        }

        if ($Exception->getCode() != 404) {
            $this->fail('\QUI\Exception->getCode not working');
        }

        if ($Exception->getType() != 'QUI\Exception') {
            $this->fail('\QUI\Exception->getType not working');
        }

        $exception = $Exception->toArray();

        if (!isset($exception['code'])) {
            $this->fail('\QUI\Exception->toArray not working');
        }

        if (!isset($exception['message'])) {
            $this->fail('\QUI\Exception->toArray not working');
        }


        $Exception->setAttribute('test', 123);
        $Exception->setAttributes([
            'attr1' => 1,
            'attr2' => 2,
            'att31' => 3
        ]);

        $Exception->setAttributes('lalalalala');

        if ($Exception->getAttribute('test') != 123) {
            $this->fail('\QUI\Exception->setAttribute or getAttribute not working');
        }

        if ($Exception->getAttribute('test1') !== false) {
            $this->fail('\QUI\Exception->getAttribute not working');
        }

        if ($Exception->getAttribute('attr1') != 1) {
            $this->fail('\QUI\Exception->setAttributes not working');
        }
    }
}
