<?php

namespace QUI\Utils;

use InvalidArgumentException;

/**
 * Normalizes the case-sensitive delivery contexts of a locale entry.
 */
final class LocaleContext
{
    /**
     * Empty input denotes the shared frontend and administration inventory.
     *
     * @throws InvalidArgumentException For invalid or reserved context names.
     */
    public static function normalize(string $contexts): string
    {
        if (trim($contexts) === '') {
            return '';
        }

        $names = [];

        foreach (explode(',', $contexts) as $context) {
            $context = trim($context);

            if (
                !preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $context)
                || in_array(strtolower($context), ['common', 'all', 'manifest'], true)
            ) {
                throw new InvalidArgumentException('Invalid or reserved locale context: ' . $context);
            }

            $names[$context] = $context;
        }

        sort($names, SORT_STRING);

        return implode(',', $names);
    }
}
