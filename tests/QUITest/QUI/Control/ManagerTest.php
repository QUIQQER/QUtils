<?php

namespace QUITest\QUI\Control;

use QUI;

/**
 * Class ManagerTest
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCSSFile()
    {
        $result = QUI\Control\Manager::getCSSFiles();
        //$this->assertNotEmpty($result);
    }
}
