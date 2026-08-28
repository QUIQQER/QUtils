<?php

namespace QUI\Utils\Security;

use DOMDocument;
use DOMElement;
use DOMNode;
use enshrined\svgSanitize\data\AttributeInterface;
use enshrined\svgSanitize\data\TagInterface;
use enshrined\svgSanitize\Sanitizer;
use Throwable;

use function array_filter;
use function array_fill_keys;
use function array_map;
use function array_unique;
use function array_values;
use function class_exists;
use function in_array;
use function is_array;
use function is_string;
use function iterator_to_array;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function preg_match;
use function preg_split;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Central SVG sanitization boundary for untrusted SVG markup.
 */
final class SvgSanitizer
{
    private const CONFIG_SECTION = 'svgSanitizer';

    /**
     * Configuration can select from this list, but cannot extend the security
     * boundary with arbitrary SVG/HTML elements.
     *
     * @var list<string>
     */
    private const SUPPORTED_TAGS = [
        'svg',
        'circle',
        'clipPath',
        'defs',
        'desc',
        'ellipse',
        'g',
        'image',
        'line',
        'linearGradient',
        'marker',
        'mask',
        'path',
        'pattern',
        'polygon',
        'polyline',
        'radialGradient',
        'rect',
        'stop',
        'symbol',
        'text',
        'title',
        'tspan',
        'use',
        'view'
    ];

    /** @var list<string> */
    private const SUPPORTED_ATTRIBUTES = [
        'aria-hidden',
        'aria-label',
        'class',
        'clip-rule',
        'cx',
        'cy',
        'd',
        'direction',
        'display',
        'dominant-baseline',
        'dx',
        'dy',
        'fill',
        'fill-opacity',
        'fill-rule',
        'font-family',
        'font-size',
        'font-style',
        'font-weight',
        'gradientTransform',
        'gradientUnits',
        'height',
        'href',
        'id',
        'marker-end',
        'marker-mid',
        'marker-start',
        'offset',
        'opacity',
        'orient',
        'overflow',
        'paint-order',
        'pathLength',
        'patternContentUnits',
        'patternTransform',
        'patternUnits',
        'points',
        'preserveAspectRatio',
        'r',
        'refX',
        'refY',
        'role',
        'rx',
        'ry',
        'shape-rendering',
        'spreadMethod',
        'stop-color',
        'stop-opacity',
        'stroke',
        'stroke-dasharray',
        'stroke-dashoffset',
        'stroke-linecap',
        'stroke-linejoin',
        'stroke-miterlimit',
        'stroke-opacity',
        'stroke-width',
        'text-anchor',
        'text-decoration',
        'text-rendering',
        'transform',
        'vector-effect',
        'viewBox',
        'visibility',
        'width',
        'x',
        'x1',
        'x2',
        'xlink:href',
        'xmlns',
        'xmlns:xlink',
        'y',
        'y1',
        'y2'
    ];

    /** @var list<string> */
    public const DEFAULT_ALLOWED_TAGS = [
        'svg',
        'circle',
        'clipPath',
        'defs',
        'desc',
        'ellipse',
        'g',
        'line',
        'linearGradient',
        'marker',
        'mask',
        'path',
        'pattern',
        'polygon',
        'polyline',
        'radialGradient',
        'rect',
        'stop',
        'symbol',
        'text',
        'title',
        'tspan',
        'view'
    ];

    /** @var list<string> */
    public const DEFAULT_ALLOWED_ATTRIBUTES = [
        'aria-hidden',
        'aria-label',
        'class',
        'clip-rule',
        'cx',
        'cy',
        'd',
        'direction',
        'display',
        'dominant-baseline',
        'dx',
        'dy',
        'fill',
        'fill-opacity',
        'fill-rule',
        'font-family',
        'font-size',
        'font-style',
        'font-weight',
        'gradientTransform',
        'gradientUnits',
        'height',
        'id',
        'marker-end',
        'marker-mid',
        'marker-start',
        'offset',
        'opacity',
        'orient',
        'overflow',
        'paint-order',
        'pathLength',
        'patternContentUnits',
        'patternTransform',
        'patternUnits',
        'points',
        'preserveAspectRatio',
        'r',
        'refX',
        'refY',
        'role',
        'rx',
        'ry',
        'shape-rendering',
        'spreadMethod',
        'stop-color',
        'stop-opacity',
        'stroke',
        'stroke-dasharray',
        'stroke-dashoffset',
        'stroke-linecap',
        'stroke-linejoin',
        'stroke-miterlimit',
        'stroke-opacity',
        'stroke-width',
        'text-anchor',
        'text-decoration',
        'text-rendering',
        'transform',
        'vector-effect',
        'viewBox',
        'visibility',
        'width',
        'x',
        'x1',
        'x2',
        'xmlns',
        'y',
        'y1',
        'y2'
    ];

    /**
     * Sanitize SVG markup. Empty output means that the input must be rejected.
     *
     * If no explicit allowlists are supplied, comma-separated values from
     * [svgSanitizer] allowedTags / allowedAttributes are used when configured.
     *
     * @param list<string>|null $allowedTags
     * @param list<string>|null $allowedAttributes
     */
    public static function sanitize(
        string $svg,
        ?array $allowedTags = null,
        ?array $allowedAttributes = null
    ): string {
        $Document = self::parseSvg($svg);

        if (!$Document instanceof DOMDocument) {
            return '';
        }

        $allowedTags = self::resolveAllowlist(
            $allowedTags,
            'allowedTags',
            self::DEFAULT_ALLOWED_TAGS,
            self::SUPPORTED_TAGS
        );
        $allowedAttributes = self::resolveAllowlist(
            $allowedAttributes,
            'allowedAttributes',
            self::DEFAULT_ALLOWED_ATTRIBUTES,
            self::SUPPORTED_ATTRIBUTES
        );

        // The SVG root and namespace are structural requirements, not optional features.
        $allowedTags[] = 'svg';
        $allowedAttributes[] = 'xmlns';
        $allowedTags = array_values(array_unique($allowedTags));
        $allowedAttributes = array_values(array_unique($allowedAttributes));

        try {
            $TagProvider = new class ($allowedTags) implements TagInterface {
                /** @var list<string> */
                private static array $tags = [];

                /** @param list<string> $tags */
                public function __construct(array $tags)
                {
                    self::$tags = $tags;
                }

                /** @return list<string> */
                public static function getTags(): array
                {
                    return self::$tags;
                }
            };
            $AttributeProvider = new class ($allowedAttributes) implements AttributeInterface {
                /** @var list<string> */
                private static array $attributes = [];

                /** @param list<string> $attributes */
                public function __construct(array $attributes)
                {
                    self::$attributes = $attributes;
                }

                /** @return list<string> */
                public static function getAttributes(): array
                {
                    return self::$attributes;
                }
            };

            $Sanitizer = new Sanitizer();
            $Sanitizer->setAllowedTags($TagProvider);
            $Sanitizer->setAllowedAttrs($AttributeProvider);
            $Sanitizer->removeRemoteReferences(true);
            $Sanitizer->removeXMLTag(true);

            $sanitized = $Sanitizer->sanitize($svg);
        } catch (Throwable) {
            return '';
        }

        if (!is_string($sanitized) || trim($sanitized) === '') {
            return '';
        }

        $SanitizedDocument = self::parseSvg($sanitized);

        if (!$SanitizedDocument instanceof DOMDocument) {
            return '';
        }

        $allowedTagLookup = array_fill_keys(array_map('strtolower', $allowedTags), true);
        $allowedAttributeLookup = array_fill_keys(array_map('strtolower', $allowedAttributes), true);
        $SanitizedRoot = $SanitizedDocument->documentElement;

        if (
            !$SanitizedRoot instanceof DOMElement
            ||
            !self::removeUnsafeContent(
                $SanitizedRoot,
                $allowedTagLookup,
                $allowedAttributeLookup
            )
        ) {
            return '';
        }

        $result = $SanitizedDocument->saveXML($SanitizedDocument->documentElement);

        return is_string($result) ? $result : '';
    }

    private static function parseSvg(string $svg): ?DOMDocument
    {
        if (trim($svg) === '') {
            return null;
        }

        $previousErrorHandling = libxml_use_internal_errors(true);

        try {
            $Document = new DOMDocument();
            $Document->resolveExternals = false;
            $Document->substituteEntities = false;

            if (!$Document->loadXML($svg, LIBXML_NONET)) {
                return null;
            }

            if ($Document->doctype !== null) {
                return null;
            }

            $Root = $Document->documentElement;

            if (!$Root instanceof DOMElement || $Root->tagName !== 'svg') {
                return null;
            }

            if (
                $Root->namespaceURI !== null
                && $Root->namespaceURI !== ''
                && $Root->namespaceURI !== 'http://www.w3.org/2000/svg'
            ) {
                return null;
            }

            if (!$Root->hasAttribute('xmlns')) {
                $Root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            }

            return $Document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
        }
    }

    /**
     * @param list<string>|null $explicit
     * @param list<string> $defaults
     * @param list<string> $supported
     * @return list<string>
     */
    private static function resolveAllowlist(
        ?array $explicit,
        string $configKey,
        array $defaults,
        array $supported
    ): array {
        $configured = $explicit;

        if ($configured === null && class_exists(\QUI::class)) {
            try {
                $value = \QUI::conf(self::CONFIG_SECTION, $configKey);

                if (is_array($value)) {
                    $configured = $value;
                } elseif (is_string($value) && trim($value) !== '') {
                    $configured = preg_split('/[\s,]+/', $value) ?: null;
                }
            } catch (Throwable) {
                $configured = null;
            }
        }

        if ($configured === null) {
            $configured = $defaults;
        }

        $configured = array_values(array_unique(array_filter(array_map(
            static function (mixed $value): string {
                return is_string($value) ? trim($value) : '';
            },
            $configured
        ))));

        $supportedByLowercaseName = [];

        foreach ($supported as $name) {
            $supportedByLowercaseName[strtolower($name)] = $name;
        }

        $result = [];

        foreach ($configured as $name) {
            $lowercaseName = strtolower($name);

            if (isset($supportedByLowercaseName[$lowercaseName])) {
                $result[] = $supportedByLowercaseName[$lowercaseName];
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Remove unsafe content that must stay forbidden even if a future upstream
     * sanitizer policy changes. Returns false for hostile namespaces.
     *
     * @param array<string, true> $allowedTags
     * @param array<string, true> $allowedAttributes
     */
    private static function removeUnsafeContent(
        DOMNode $Node,
        array $allowedTags,
        array $allowedAttributes
    ): bool {
        if ($Node instanceof DOMElement) {
            if (
                $Node->namespaceURI !== null
                && $Node->namespaceURI !== ''
                && $Node->namespaceURI !== 'http://www.w3.org/2000/svg'
            ) {
                return false;
            }

            if (!isset($allowedTags[strtolower($Node->tagName)])) {
                if ($Node->parentNode === null) {
                    return false;
                }

                $Node->parentNode->removeChild($Node);
                return true;
            }

            if (
                $Node->parentNode !== null
                && in_array(strtolower($Node->tagName), [
                    'script',
                    'style',
                    'foreignobject',
                    'iframe',
                    'object',
                    'embed'
                ], true)
            ) {
                $Node->parentNode->removeChild($Node);
                return true;
            }

            foreach (iterator_to_array($Node->attributes) as $Attribute) {
                $name = strtolower($Attribute->nodeName);
                $value = strtolower(trim((string)$Attribute->nodeValue));

                if (
                    !isset($allowedAttributes[$name])
                    || str_starts_with($name, 'on')
                    || $name === 'style'
                    || str_contains($value, 'javascript:')
                    || str_contains($value, 'data:')
                    || str_contains($value, '@import')
                    || str_contains($value, 'expression(')
                    || str_contains($value, '-moz-binding')
                    || str_contains($value, 'behavior:')
                    || str_contains($value, '/*')
                    || str_contains($value, '\\')
                    || preg_match('/[\x00-\x1F\x7F]/u', $value) === 1
                    || (
                        str_contains($value, 'url(')
                        && preg_match('/^url\(\s*#[A-Za-z_][A-Za-z0-9_.:-]*\s*\)$/D', $value) !== 1
                    )
                    || (
                        str_contains($name, 'href')
                        && $value !== ''
                        && !str_starts_with($value, '#')
                    )
                ) {
                    $Node->removeAttributeNode($Attribute);
                }
            }
        }

        foreach (iterator_to_array($Node->childNodes) as $Child) {
            if (!self::removeUnsafeContent($Child, $allowedTags, $allowedAttributes)) {
                return false;
            }
        }

        return true;
    }
}
