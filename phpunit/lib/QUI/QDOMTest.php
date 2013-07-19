<?php

use QUI\QDOM as QDOM;

class QDOMTest extends PHPUnit_Framework_TestCase
{
    public function testToString()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        $string = (string)$Test;

        if ( $string !== 'Object QUI\QDOM();' ) {
            $this->fail( get_class($Test) .'__toString has an error' );
        }

        $Test = new QDOM();
        $Test->setAttributes(array(
            'name' => 'huhu'
        ));

        $string = (string)$Test;

        if ( $string !== 'Object QUI\QDOM(huhu);' ) {
            $this->fail( get_class($Test) .'__toString has an error' );
        }
    }

    public function testExistsAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        if ($Test->existsAttribute('var1') !== true) {
            $this->fail( get_class($Test) .'->existsAttribute ' );
        }

        if ($Test->existsAttribute('var2') !== true) {
            $this->fail( get_class($Test) .'->existsAttribute' );
        }
    }

    public function testGetAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        if ($Test->getAttribute('var1') !== 123) {
            $this->fail( get_class($Test) .'->getAttribute var1' );
        }

        if ($Test->getAttribute('var2') !== 234) {
            $this->fail( get_class($Test) .'->getAttribute var2' );
        }
    }

    public function testSetAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        if ($Test->getAttribute('var1') !== 123) {
            $this->fail( get_class($Test) .'->setAttribute var1' );
        }
    }

    public function testSetAttributes()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        if ($Test->getAttribute('var1') !== 123) {
            $this->fail( get_class($Test) .'->getAttribute var1' );
        }

        if ($Test->getAttribute('var2') !== 234) {
            $this->fail( get_class($Test) .'->getAttribute var2' );
        }

        // kein array übergebn
        $Test->setAttributes(false);
    }

    public function testRemoveAttribute()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        $Test->removeAttribute('var1');

        if ($Test->existsAttribute('var1') !== false) {
            $this->fail( get_class($Test) .'->removeAttribute var1' );
        }

        if ($Test->getAttribute('var1') !== false) {
            $this->fail( get_class($Test) .'->removeAttribute var1' );
        }

        if ($Test->getAttribute('var2') === false) {
            $this->fail( get_class($Test) .'->removeAttribute var2' );
        }
    }

    public function testGetAttributes()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        $attributes = $Test->getAttributes();

        if ($attributes['var1'] !== 123) {
            $this->fail( get_class($Test) .'->getAttribute var1' );
        }

        if ($attributes['var2'] !== 234) {
            $this->fail( get_class($Test) .'->getAttribute var2' );
        }

        /**
         * depricated
         */
        $attributes = $Test->getAllAttributes();

        if ($attributes['var1'] !== 123) {
            $this->fail( get_class($Test) .'->getAttribute var1' );
        }

        if ($attributes['var2'] !== 234) {
            $this->fail( get_class($Test) .'->getAttribute var2' );
        }
    }

    public function testGetType()
    {
        $Test = new QDOM();
        $Test->setAttributes(array(
            'var1' => 123,
            'var2' => 234
        ));

        $type = $Test->getType();

        if (!is_string($type)) {
            $this->fail( get_class($Test) .'->getType' );
        }
    }
}
