<?php

namespace QUITest\QUI\Utils;

use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Utils\Convert;

class ConvertTest extends TestCase
{
    #[DataProvider('priceProvider')]
    public function testFormPrice(int $price, int $type, string $expected): void
    {
        $this->assertSame($expected, Convert::formPrice($price, $type));
    }

    public static function priceProvider(): array
    {
        return [
            'default' => [1234, 1, '1234'],
            'german separators' => [1234, 2, '1.234,00'],
            'english separators' => [1234, 3, '1,234.00']
        ];
    }

    public function testFormatBytes(): void
    {
        $this->assertSame('0 B', Convert::formatBytes(0));
        $this->assertSame('1.00 KB', Convert::formatBytes(1024));
        $this->assertSame('1.50 MB', Convert::formatBytes(1572864));
    }

    public function testCharacterConversions(): void
    {
        $this->assertSame("\xC4\xE4\xD6\xF6\xDC\xFC\xDF\x27\xB4\x60", Convert::convertChars('ÄäÖöÜüß\'´`'));
        $this->assertSame('Ae ae Oe oe Ue ue sz', Convert::convertUrlChars('Ä ä Ö ö Ü ü ß'));
        $this->assertSame('Ä ä Ö ö Ü ü ß', Convert::convertUrlChars('Ae ae Oe oe Ue ue sz', 1));
        $this->assertSame('AEneid Lodz OEuvre ueber', Convert::convertRoman('Æneid Łódź Œuvre über'));
    }

    public function testDateTimeConversions(): void
    {
        $DateTime = new DateTime('2024-05-06 07:08:09');

        $this->assertSame('2024-05-06 07:08:09', Convert::convertToMysqlDatetime($DateTime));
        $this->assertSame($DateTime->getTimestamp(), Convert::convertMySqlDatetime('2024-05-06 07:08:09'));
    }

    public function testColorBrightness(): void
    {
        $this->assertSame('#ffffff', Convert::colorBrightness('#ffffff', 0.5));
        $this->assertSame('808080', Convert::colorBrightness('ffffff', -0.5));
        $this->assertSame('#ffffff', Convert::colorBrightness('#ffffff', 2));
        $this->assertSame('#000000', Convert::colorBrightness('#ffffff', 0));
    }
}
