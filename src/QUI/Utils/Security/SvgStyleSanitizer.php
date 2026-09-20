<?php

namespace QUI\Utils\Security;

use DOMElement;

/**
 * Internal SVG presentation-CSS filter, not a general CSS parser.
 *
 * Only static type, class and ID selectors (including compounds and lists)
 * are supported. Stylesheets are inlined so their rules cannot affect HTML
 * outside an embedded SVG. At-rules, combinators and dynamic CSS are excluded.
 *
 * @internal
 */
final class SvgStyleSanitizer
{
    /** @var list<string> */
    private const PROPERTIES = [
        'clip-path', 'clip-rule', 'color', 'direction', 'display', 'dominant-baseline',
        'fill', 'fill-opacity', 'fill-rule', 'font-family', 'font-size', 'font-style',
        'font-weight', 'marker-end', 'marker-mid', 'marker-start', 'opacity', 'overflow',
        'paint-order', 'shape-rendering', 'stop-color', 'stop-opacity', 'stroke',
        'stroke-dasharray', 'stroke-dashoffset', 'stroke-linecap', 'stroke-linejoin',
        'stroke-miterlimit', 'stroke-opacity', 'stroke-width', 'text-anchor',
        'text-decoration', 'text-rendering', 'vector-effect', 'visibility'
    ];

    /**
     * Filter declarations while retaining their order and !important flags.
     * The browser still resolves invalid values, inheritance and fallbacks.
     *
     * @param array<string, true> $allowedAttributes
     */
    public static function sanitizeDeclarations(string $css, array $allowedAttributes): string
    {
        $css = self::removeComments($css);
        $declarations = [];

        foreach (explode(';', $css) as $declaration) {
            $parts = explode(':', $declaration, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $property = strtolower(trim($parts[0]));

            if (!isset($allowedAttributes[$property]) || !in_array($property, self::PROPERTIES, true)) {
                continue;
            }

            $value = trim($parts[1]);
            $important = preg_match('/\s*!\s*important\s*$/i', $value) === 1;
            $value = trim(preg_replace('/\s*!\s*important\s*$/i', '', $value) ?? '');

            if (preg_match('/^url\(\s*([\'"]?)(#[A-Za-z_][A-Za-z0-9_.:-]*)\1\s*\)$/iD', $value, $reference)) {
                if (!in_array($property, ['fill', 'stroke', 'clip-path', 'marker-end', 'marker-mid', 'marker-start'], true)) {
                    continue;
                }

                $value = 'url(' . $reference[2] . ')';
            } elseif (
                // No escapes, arbitrary functions, URL schemes or CSS syntax can survive this grammar.
                preg_match('/^(?:[-a-zA-Z0-9#.,%+\s]|"[a-zA-Z0-9 _-]+"|\'[a-zA-Z0-9 _-]+\')+$/D', $value) !== 1
                && preg_match('/^(?:rgb|rgba|hsl|hsla)\((?:[-+0-9.,%\s\/]|deg|grad|rad|turn)+\)$/iD', $value) !== 1
            ) {
                continue;
            }

            if (preg_match('/[\x00-\x08\x0B\x0E-\x1F\x7F]/', $value)) {
                continue;
            }

            $value = preg_replace('/\s+/', ' ', $value) ?? '';
            $declarations[] = $property . ':' . $value . ($important ? ' !important' : '');
        }

        return implode(';', $declarations);
    }

    /**
     * @param array<string, true> $allowedTags
     * @param array<string, true> $allowedAttributes
     */
    public static function inlineStyles(
        DOMElement $Root,
        array $allowedTags,
        array $allowedAttributes
    ): void {
        if (!isset($allowedAttributes['style'])) {
            return;
        }

        $elements = [];
        $stylesheets = [];
        self::collectElements($Root, $allowedTags, $elements, $stylesheets);
        $rules = [];

        foreach ($stylesheets as $css) {
            foreach (self::parseStylesheet($css, $allowedAttributes) as $rule) {
                $rules[] = $rule;
            }
        }

        foreach ($elements as $Element) {
            $matching = [];

            foreach ($rules as $order => $rule) {
                $specificity = null;

                foreach ($rule['selectors'] as $selector) {
                    $rank = self::matchSelector($Element, $selector);

                    if ($rank !== null && ($specificity === null || $rank > $specificity)) {
                        $specificity = $rank;
                    }
                }

                if ($specificity !== null) {
                    $matching[] = ['rank' => [...$specificity, $order], 'css' => $rule['css']];
                }
            }

            usort($matching, static fn(array $left, array $right): int => $left['rank'] <=> $right['rank']);
            $declarations = array_column($matching, 'css');
            $declarations[] = self::sanitizeDeclarations($Element->getAttribute('style'), $allowedAttributes);
            $css = implode(';', array_filter($declarations, static fn(string $value): bool => $value !== ''));

            if ($css === '') {
                $Element->removeAttribute('style');
            } else {
                $Element->setAttribute('style', $css);
            }
        }
    }

    /**
     * Do not import CSS or elements from subtrees the SVG sanitizer will discard.
     *
     * @param array<string, true> $allowedTags
     * @param list<DOMElement> $elements
     * @param list<string> $stylesheets
     */
    private static function collectElements(
        DOMElement $Element,
        array $allowedTags,
        array &$elements,
        array &$stylesheets
    ): void {
        if ($Element->namespaceURI !== null && $Element->namespaceURI !== 'http://www.w3.org/2000/svg') {
            return;
        }

        if ($Element->tagName === 'style') {
            if (
                in_array(strtolower(trim($Element->getAttribute('type'))), ['', 'text/css'], true)
                && in_array(strtolower(trim($Element->getAttribute('media'))), ['', 'all'], true)
            ) {
                $stylesheets[] = $Element->textContent;
            }

            return;
        }

        if (!isset($allowedTags[strtolower($Element->tagName)])) {
            return;
        }

        $elements[] = $Element;

        foreach ($Element->childNodes as $Child) {
            if ($Child instanceof DOMElement) {
                self::collectElements($Child, $allowedTags, $elements, $stylesheets);
            }
        }
    }

    /**
     * @param array<string, true> $allowedAttributes
     * @return list<array{selectors: list<string>, css: string}>
     */
    private static function parseStylesheet(string $css, array $allowedAttributes): array
    {
        $css = trim(self::removeComments($css));
        $offset = 0;
        $rules = [];

        while ($offset < strlen($css)) {
            // Reject nested/conditional rules instead of accidentally making their contents unconditional.
            if (!preg_match('/\G\s*([^{}@]+)\{([^{}]*)\}\s*/', $css, $match, 0, $offset)) {
                return [];
            }

            $offset += strlen($match[0]);
            $selectors = array_map('trim', explode(',', $match[1]));

            foreach ($selectors as $selector) {
                if (
                    $selector === ''
                    || preg_match(
                        '/^(?:[a-zA-Z_][a-zA-Z0-9_-]*|\*)?(?:[.#][a-zA-Z_][a-zA-Z0-9_-]*)*$/D',
                        $selector
                    ) !== 1
                ) {
                    continue 2;
                }
            }

            $declarations = self::sanitizeDeclarations($match[2], $allowedAttributes);

            if ($declarations !== '') {
                $rules[] = ['selectors' => $selectors, 'css' => $declarations];
            }
        }

        return $rules;
    }

    /** @return array{int, int, int}|null */
    private static function matchSelector(DOMElement $Element, string $selector): ?array
    {
        preg_match_all('/[.#]?[a-zA-Z_][a-zA-Z0-9_-]*|\*/', $selector, $parts);
        $classes = preg_split('/\s+/', trim($Element->getAttribute('class'))) ?: [];
        $rank = [0, 0, 0];

        foreach ($parts[0] as $part) {
            if ($part[0] === '#') {
                if ($Element->getAttribute('id') !== substr($part, 1)) {
                    return null;
                }

                $rank[0]++;
            } elseif ($part[0] === '.') {
                if (!in_array(substr($part, 1), $classes, true)) {
                    return null;
                }

                $rank[1]++;
            } elseif ($part !== '*') {
                if ($Element->tagName !== $part) {
                    return null;
                }

                $rank[2]++;
            }
        }

        return $rank;
    }

    private static function removeComments(string $css): string
    {
        $css = preg_replace('~/\*.*?\*/~s', '', $css) ?? '';

        return str_contains($css, '/*') ? '' : $css;
    }
}
