<?php

namespace QUI\Control;

use QUI;
class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCSSFile() {
        $result = QUI\Control\Manager::getCSSFiles();

        var_dump($result);
        $this->assertNotEmpty($result);
    }
}
