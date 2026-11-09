<?php

namespace QUITest\QUI\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QUI\Utils\LocaleContext;

class LocaleContextTest extends TestCase
{
    /**
     * @dataProvider validContexts
     */
    public function testNormalizesContexts(string $input, string $expected): void
    {
        $this->assertSame($expected, LocaleContext::normalize($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validContexts(): array
    {
        return [
            'empty' => ['', ''],
            'whitespace' => [" \t\n", ''],
            'frontend' => ['frontend', 'frontend'],
            'administration' => ['administration', 'administration'],
            'both' => ['frontend,administration', 'administration,frontend'],
            'own context' => ['namescoreDashboard', 'namescoreDashboard'],
            'mixed' => ['namescoreDashboard, administration', 'administration,namescoreDashboard'],
            'multiple own contexts' => ['secondDashboard,firstDashboard', 'firstDashboard,secondDashboard'],
            'duplicates' => [' frontend,administration, frontend ', 'administration,frontend'],
            'case sensitive' => ['dashboard,Dashboard', 'Dashboard,dashboard'],
            'punctuation' => ['dashboard_2,dashboard-1', 'dashboard-1,dashboard_2'],
            'maximum length' => [str_repeat('a', 64), str_repeat('a', 64)]
        ];
    }

    /**
     * @dataProvider invalidContexts
     */
    public function testRejectsInvalidContexts(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        LocaleContext::normalize($input);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidContexts(): array
    {
        return [
            'leading comma' => [',frontend'],
            'trailing comma' => ['frontend,'],
            'empty token' => ['frontend, ,administration'],
            'only comma' => [','],
            'path' => ['../frontend'],
            'slash' => ['front/end'],
            'backslash' => ['front\\end'],
            'query' => ['frontend?version=1'],
            'fragment' => ['frontend#test'],
            'dot' => ['frontend.admin'],
            'internal cache' => ['_cache'],
            'internal contexts' => ['_contexts'],
            'reserved common' => ['common'],
            'reserved all' => ['all'],
            'reserved manifest' => ['manifest'],
            'reserved uppercase' => ['MANIFEST'],
            'reserved mixed case' => ['Common'],
            'space' => ['front end'],
            'control character' => ["front\nend"],
            'unicode' => ['übersicht'],
            'digit prefix' => ['2dashboard'],
            'too long' => [str_repeat('a', 65)]
        ];
    }
}
