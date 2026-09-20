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
    public function testInlineStylesAndClippingArePreserved(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
            . '<defs><clipPath id="clip" clipPathUnits="userSpaceOnUse"><rect width="20" height="20"/></clipPath>'
            . '<radialGradient id="gradient"><stop offset="0" style="stop-color:#aeb2d5;stop-opacity:1"/>'
            . '</radialGradient></defs>'
            . '<g clip-path="url(#clip)"><path d="M0 0h20v20H0z" '
            . 'style="fill:url(#gradient);stroke:#fff;stroke-width:2;opacity:0.5"/></g></svg>'
        );

        self::assertStringContainsString('clipPathUnits="userSpaceOnUse"', $clean);
        self::assertStringContainsString('clip-path="url(#clip)"', $clean);
        self::assertStringContainsString('stop-color:#aeb2d5', $clean);
        self::assertStringContainsString('fill:url(#gradient)', $clean);
        self::assertStringContainsString('stroke:#fff', $clean);
        self::assertSame($clean, SvgSanitizer::sanitize($clean));
        self::assertSvgHasNoActiveContent($clean);
    }

    public function testStylesheetRulesAreScopedToMatchingSvgElements(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><style>'
            . '.paint, .other {fill:#056268;stroke-width:0px} '
            . 'path.paint {stroke:#fff} #shape {fill:#777bb3}'
            . '</style><path id="shape" class="paint" d="M0 0" style="fill:#f47216"/>'
            . '<circle class="paint" r="1"/><rect class="unrelated" width="1" height="1"/></svg>'
        );
        $Document = new DOMDocument();
        $Document->loadXML($clean, LIBXML_NONET);
        $path = $Document->getElementsByTagName('path')->item(0);
        $circle = $Document->getElementsByTagName('circle')->item(0);
        $rect = $Document->getElementsByTagName('rect')->item(0);

        self::assertInstanceOf(DOMElement::class, $path);
        self::assertInstanceOf(DOMElement::class, $circle);
        self::assertInstanceOf(DOMElement::class, $rect);
        self::assertSame('fill:#056268;stroke-width:0px;stroke:#fff;fill:#777bb3;fill:#f47216', $path->getAttribute('style'));
        self::assertSame('fill:#056268;stroke-width:0px', $circle->getAttribute('style'));
        self::assertFalse($rect->hasAttribute('style'));
        self::assertSame($clean, SvgSanitizer::sanitize($clean));
        self::assertSvgHasNoActiveContent($clean);
    }

    public function testImportantDeclarationsAndPresentationFallbackArePreserved(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><style>'
            . '#shape {fill:blue} .paint {fill:red !important}'
            . '</style><path id="shape" class="paint" fill="green" style="fill:orange;fill:invalid-color"/></svg>'
        );

        self::assertStringContainsString('fill="green"', $clean);
        self::assertStringContainsString('style="fill:red !important;fill:blue;fill:orange;fill:invalid-color"', $clean);
        self::assertSame($clean, SvgSanitizer::sanitize($clean));
    }

    public function testStylePropertiesRespectTheAttributeAllowlist(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><path style="fill:red;stroke:blue"/></svg>',
            ['svg', 'path'],
            ['xmlns', 'style', 'fill']
        );

        self::assertStringContainsString('style="fill:red"', $clean);
        self::assertStringNotContainsString('stroke', $clean);
    }

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

    #[DataProvider('unsafeStyleProvider')]
    public function testUnsafeStyleDeclarationsAreRemoved(string $declaration): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.paint {' . $declaration . ';stroke:#fff}</style>'
            . '<path class="paint" style="' . htmlspecialchars($declaration, ENT_QUOTES | ENT_XML1)
            . ';opacity:0.5"/></svg>'
        );
        $Document = new DOMDocument();
        $Document->loadXML($clean, LIBXML_NONET);
        $path = $Document->getElementsByTagName('path')->item(0);

        self::assertInstanceOf(DOMElement::class, $path);
        self::assertSame('stroke:#fff;opacity:0.5', $path->getAttribute('style'));
        self::assertSvgHasNoActiveContent($clean);
    }

    /** @return array<string, array{string}> */
    public static function unsafeStyleProvider(): array
    {
        return [
            'remote paint' => ['fill:url(https://attacker.invalid/paint.svg#x)'],
            'quoted remote paint' => ['fill:url("https://attacker.invalid/paint.svg#x")'],
            'protocol relative paint' => ['fill:url(//attacker.invalid/paint.svg#x)'],
            'relative paint' => ['fill:url(other.svg#x)'],
            'data paint' => ['fill:url(data:image/svg+xml,payload)'],
            'javascript paint' => ['fill:url(javascript:alert(1))'],
            'escaped url' => ['fill:u\\72l(https://attacker.invalid/a)'],
            'escaped local reference' => ['fill:url(\\23gradient)'],
            'comment obfuscation' => ['fill:u/**/rl(https://attacker.invalid/a)'],
            'expression' => ['fill:expression(alert(1))'],
            'custom property function' => ['fill:var(--remote-paint)'],
            'custom property definition' => ['--remote-paint:url(https://attacker.invalid/a)'],
            'behavior' => ['behavior:url(https://attacker.invalid/a)'],
            'binding' => ['-moz-binding:url(https://attacker.invalid/a)'],
            'geometry property' => ['d:path("M0 0")'],
            'attribute injection' => ['fill:red" onload="alert(1)'],
            'unsafe clipping' => ['clip-path:url(https://attacker.invalid/a#clip)'],
            'unsupported function' => ['filter:blur(3px)']
        ];
    }

    #[DataProvider('unsupportedStylesheetProvider')]
    public function testUnsupportedStylesheetsAreNotAppliedUnconditionally(string $stylesheet): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><style>' . htmlspecialchars($stylesheet, ENT_XML1) . '</style>'
            . '<path id="shape" class="paint" fill="#123456"/></svg>'
        );

        self::assertStringNotContainsString('style=', $clean);
        self::assertStringContainsString('fill="#123456"', $clean);
        self::assertSvgHasNoActiveContent($clean);
    }

    /** @return array<string, array{string}> */
    public static function unsupportedStylesheetProvider(): array
    {
        return [
            'media rule' => ['@media print {.paint {fill:red}}'],
            'import' => ['@import url(https://attacker.invalid/a); .paint {fill:red}'],
            'hover' => ['.paint:hover {fill:red}'],
            'descendant' => ['g .paint {fill:red}'],
            'invalid selector list' => ['.paint, #shape:hover {fill:red}'],
            'attribute selector' => ['[id="shape"] {fill:red}'],
            'escaped selector' => ['.p\\61int {fill:red}'],
            'nested rule' => ['.paint {& {fill:red}}']
        ];
    }

    public function testPresentationValuesAndQuotedLocalReferencesArePreserved(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" style="color:rgb(12 34 56 / 50%)">'
            . '<path style="fill:url(\'#Gradient\');stroke:currentColor;font-family:\'Open Sans\', sans-serif;'
            . 'opacity:0.5 ! important;stroke-width:2px"/></svg>'
        );

        self::assertStringContainsString('color:rgb(12 34 56 / 50%)', $clean);
        self::assertStringContainsString('fill:url(#Gradient)', $clean);
        self::assertStringContainsString('stroke:currentColor', $clean);
        self::assertStringContainsString('opacity:0.5 !important', $clean);
        self::assertSame($clean, SvgSanitizer::sanitize($clean));
        self::assertSvgHasNoActiveContent($clean);
    }

    public function testStyleRulesFromRemovedSubtreesAreIgnored(): void
    {
        $clean = SvgSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject>'
            . '<style>.paint {fill:red}</style></foreignObject>'
            . '<style media="print">.paint {fill:blue}</style>'
            . '<path class="paint" fill="#123456"/></svg>'
        );

        self::assertStringNotContainsString('style=', $clean);
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
                self::assertStringNotContainsString('javascript:', $value);
                self::assertStringNotContainsString('data:', $value);
                self::assertStringNotContainsString('@import', $value);
                self::assertStringNotContainsString('expression(', $value);
                self::assertStringNotContainsString('var(', $value);
                self::assertStringNotContainsString('/*', $value);
                self::assertStringNotContainsString('\\', $value);

                if (!str_starts_with($name, 'xmlns')) {
                    self::assertStringNotContainsString('https://', $value);
                    self::assertStringNotContainsString('http://', $value);
                    self::assertStringNotContainsString('file:', $value);
                }
                if (str_contains($value, 'url(')) {
                    preg_match_all('/url\([^)]*\)/', $value, $references);

                    foreach ($references[0] as $reference) {
                        self::assertMatchesRegularExpression(
                            '/^url\(\s*#[A-Za-z_][A-Za-z0-9_.:-]*\s*\)$/D',
                            $reference
                        );
                    }
                }

                if (str_contains($name, 'href') && $value !== '') {
                    self::assertStringStartsWith('#', $value);
                }
            }
        }
    }
}
