<?php

namespace QUITest\QUI\Control;

use QUI;

/**
 * Class ManagerTest
 */
class ManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCSSFile()
    {
        $result = QUI\Control\Manager::getCSSFiles();
        $this->assertIsArray($result);
    }
}
