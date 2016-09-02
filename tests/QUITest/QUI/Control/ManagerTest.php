<?php

namespace QUITest\QUI\Control;

use QUI;

/**
 * Class ManagerTest
 * @package QUITest\Control
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCSSFile()
    {
        $result = QUI\Control\Manager::getCSSFiles();

        var_dump($result);
        $this->assertNotEmpty($result);
    }
}
