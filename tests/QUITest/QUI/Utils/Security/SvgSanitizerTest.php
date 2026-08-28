<?php

namespace QUITests\QUI\Utils\Security;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Utils\Security\SvgSanitizer;

use function iterator_to_array;
use function strtolower;
use function trim;

class SvgSanitizerTest extends TestCase
{
    public function testHarmlessSvgIsPreserved(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
            . '<title>Safe icon</title>'
            . '<g fill="#123456"><path d="M0 0h20v20H0z"/></g>'
            . '</svg>'
        );

        self::assertNotSame('', $clean);
        self::assertStringContainsString('<title>Safe icon</title>', $clean);
        self::assertStringContainsString('fill="#123456"', $clean);
        self::assertSvgHasNoActiveContent($clean);
    }

    public function testSanitizationIsIdempotent(): void
    {
        $once = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            . '<path fill="url(#gradient)" d="M0 0h10v10H0z"/>'
            . '</svg>'
        );

        self::assertNotSame('', $once);
        self::assertSame($once, SvgSanitizer::sanitize($once));
    }

    /**
     * @param string $svg
     */
    #[DataProvider('maliciousSvgProvider')]
    public function testActiveContentIsRemoved(string $svg): void
    {
        $clean = SvgSanitizer::sanitize($svg);

        self::assertNotSame('', $clean);
        self::assertSvgHasNoActiveContent($clean);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function maliciousSvgProvider(): array
    {
        $start = '<svg xmlns="http://www.w3.org/2000/svg" '
            . 'xmlns:xlink="http://www.w3.org/1999/xlink">';
        $end = '<rect width="10" height="10"/></svg>';

        return [
            'script' => [$start . '<script>alert(document.domain)</script>' . $end],
            'event handler' => [$start . '<g onload="alert(1)" onclick="alert(2)"/>' . $end],
            'foreign object' => [
                $start . '<foreignObject><iframe src="https://attacker.invalid"/></foreignObject>' . $end
            ],
            'javascript href' => [$start . '<a href="javascript:alert(1)"><text>x</text></a>' . $end],
            'javascript xlink href' => [
                $start . '<use xlink:HrEf="javascript:alert(1)"/>' . $end
            ],
            'encoded javascript href' => [
                $start . '<use href="jav&#x61;script:alert(1)"/>' . $end
            ],
            'external image' => [
                $start . '<image href="https://attacker.invalid/tracker.png"/>' . $end
            ],
            'external css url' => [
                $start . '<rect style="fill:url(https://attacker.invalid/a.svg)"/>' . $end
            ],
            'css import' => [
                $start . '<style>@import url(https://attacker.invalid/a.css);</style>' . $end
            ],
            'escaped css url' => [
                $start . '<rect fill="u\\72l(https://attacker.invalid/a.svg)"/>' . $end
            ],
            'comment-obfuscated css url' => [
                $start . '<rect fill="u/**/rl(https://attacker.invalid/a.svg)"/>' . $end
            ],
            'dangerous data URL' => [
                $start . '<image href="data:image/svg+xml,&lt;svg onload=alert(1)&gt;"/>' . $end
            ],
            'foreign namespace' => [
                $start . '<x:script xmlns:x="urn:attacker">alert(1)</x:script>' . $end
            ]
        ];
    }

    public function testDoctypeAndExternalEntityAreRejected(): void
    {
        $svg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            . '<svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>';

        self::assertSame('', SvgSanitizer::sanitize($svg));
    }

    public function testMalformedSvgIsRejected(): void
    {
        self::assertSame('', SvgSanitizer::sanitize('<svg><g></svg>'));
        self::assertSame('', SvgSanitizer::sanitize('<html><svg/></html>'));
        self::assertSame('', SvgSanitizer::sanitize('<x:svg xmlns:x="urn:attacker"/>'));
    }

    public function testExplicitAllowlistsCanRestrictDefaults(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="5"/><path d="M0 0"/></svg>',
            ['svg', 'path'],
            ['xmlns', 'd']
        );

        self::assertStringNotContainsString('<circle', $clean);
        self::assertStringContainsString('<path', $clean);
        self::assertSvgHasNoActiveContent($clean);
    }

    public function testExplicitReferenceAttributesCannotEnableExternalResources(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<defs><path id="shape" d="M0 0"/></defs>'
            . '<use href="#shape"/>'
            . '<image href="https://attacker.invalid/a.png"/>'
            . '</svg>',
            ['svg', 'defs', 'path', 'use', 'image'],
            ['xmlns', 'id', 'd', 'href']
        );

        self::assertStringContainsString('href="#shape"', $clean);
        self::assertStringNotContainsString('attacker.invalid', $clean);
        self::assertSvgHasNoActiveContent($clean);
    }

    public function testQuiqqerConfigurationSelectsSupportedPolicy(): void
    {
        $Config = \QUI::getConfig('etc/conf.ini.php');
        $originalTags = $Config->get('svgSanitizer', 'allowedTags');
        $originalAttributes = $Config->get('svgSanitizer', 'allowedAttributes');

        try {
            $Config->set('svgSanitizer', 'allowedTags', 'svg,defs,path,use');
            $Config->set('svgSanitizer', 'allowedAttributes', 'xmlns,id,d,href');

            $clean = SvgSanitizer::sanitize(
                '<svg xmlns="http://www.w3.org/2000/svg">'
                . '<defs><path id="shape" d="M0 0"/></defs>'
                . '<use href="#shape"/><circle cx="5" cy="5" r="5"/>'
                . '</svg>'
            );

            self::assertStringContainsString('<use href="#shape"', $clean);
            self::assertStringNotContainsString('<circle', $clean);
            self::assertSvgHasNoActiveContent($clean);
        } finally {
            if ($originalTags === false) {
                $Config->del('svgSanitizer', 'allowedTags');
            } else {
                $Config->set('svgSanitizer', 'allowedTags', $originalTags);
            }

            if ($originalAttributes === false) {
                $Config->del('svgSanitizer', 'allowedAttributes');
            } else {
                $Config->set('svgSanitizer', 'allowedAttributes', $originalAttributes);
            }
        }
    }

    public function testConfigurationCannotEnableUnsupportedActiveContent(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<script>alert(1)</script><style>@import url(https://attacker.invalid/a.css)</style>'
            . '<rect width="10" height="10" onclick="alert(2)"/>'
            . '</svg>',
            ['svg', 'script', 'style', 'rect'],
            ['xmlns', 'width', 'onclick', 'style']
        );

        self::assertNotSame('', $clean);
        self::assertSvgHasNoActiveContent($clean);
    }

    public function testSafeInternalPaintReferenceCanBePreserved(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<defs><linearGradient id="gradient"><stop offset="0" stop-color="#fff"/></linearGradient></defs>'
            . '<rect width="10" height="10" fill="url(#gradient)"/>'
            . '</svg>'
        );

        self::assertStringContainsString('fill="url(#gradient)"', $clean);
        self::assertSvgHasNoActiveContent($clean);
    }

    private static function assertSvgHasNoActiveContent(string $svg): void
    {
        $Document = new DOMDocument();
        self::assertTrue($Document->loadXML($svg, LIBXML_NONET));
        self::assertNull($Document->doctype);
        self::assertSame('svg', $Document->documentElement?->tagName);

        $XPath = new DOMXPath($Document);
        $activeElements = $XPath->query(
            '//*[translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")='
            . '"script" or translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")='
            . '"style" or translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")='
            . '"foreignobject" or translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")='
            . '"iframe" or translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")='
            . '"object" or translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="embed"]'
        );

        self::assertNotFalse($activeElements);
        self::assertSame(0, $activeElements->length);

        $Elements = $XPath->query('//*');
        self::assertNotFalse($Elements);

        foreach ($Elements as $Element) {
            if (!$Element instanceof DOMElement) {
                continue;
            }

            foreach (iterator_to_array($Element->attributes) as $Attribute) {
                $name = strtolower($Attribute->nodeName);
                $value = strtolower(trim((string)$Attribute->nodeValue));

                self::assertFalse(str_starts_with($name, 'on'));
                self::assertNotSame('style', $name);
                self::assertStringNotContainsString('javascript:', $value);
                self::assertStringNotContainsString('data:', $value);
                self::assertStringNotContainsString('@import', $value);
                self::assertStringNotContainsString('/*', $value);
                self::assertStringNotContainsString('\\', $value);

                if (!str_starts_with($name, 'xmlns')) {
                    self::assertStringNotContainsString('https://', $value);
                    self::assertStringNotContainsString('http://', $value);
                    self::assertStringNotContainsString('file:', $value);
                }
                if (str_contains($value, 'url(')) {
                    self::assertMatchesRegularExpression(
                        '/^url\(\s*#[A-Za-z_][A-Za-z0-9_.:-]*\s*\)$/D',
                        $value
                    );
                }

                if (str_contains($name, 'href') && $value !== '') {
                    self::assertStringStartsWith('#', $value);
                }
            }
        }
    }
}
