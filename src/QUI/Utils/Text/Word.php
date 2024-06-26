<?php

/**
 * This file contains QUI\Utils\Text\Word
 */

namespace QUI\Utils\Text;

use function array_reverse;
use function asort;
use function explode;
use function in_array;
use function preg_match;
use function str_replace;
use function strip_tags;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function utf8_encode;

mb_internal_encoding('UTF-8');

/**
 * Helper for word handling
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @deprecated
 */
class Word
{
    /**
     * Zählt die wichtigen Wörter eines deutschen Textes
     *
     * @param string $text
     *
     * @return array
     */
    public static function countImportantWords(string $text): array
    {
        $str = $text;
        $str = strip_tags($str);
        $str = explode(' ', $str);

        $result = [];

        foreach ($str as $entry) {
            if (!self::isUseful($entry)) {
                continue;
            }

            if (isset($result[$entry])) {
                $result[$entry]++;
                continue;
            }

            $result[$entry] = 1;
        }

        asort($result);
        return array_reverse($result);
    }

    /**
     * Is the word usefull?
     * a word filter for german words. checks the string
     * at the moment only for german words
     *
     * @param string $word - German word
     *
     * @return boolean
     */
    public static function isUseful(string $word): bool
    {
        if (strlen($word) <= 1) {
            return false;
        }

        // Kleingeschriebene Wörter raus
        if (strtolower(mb_substr($word, 0, 1)) == mb_substr($word, 0, 1)) {
            return false;
        }

        if (preg_match('/[^a-zA-Z]/i', $word)) {
            return false;
        }

        // Wörter mit 2 Grossbuchstaben
        $w0 = utf8_encode(
            strtoupper(
                str_replace(
                    ['ä', 'ö', 'ü'],
                    ['Ä', 'Ö', 'Ü'],
                    mb_substr($word, 0, 1)
                )
            )
        );

        $w1 = utf8_encode(
            strtoupper(
                str_replace(
                    ['ä', 'ö', 'ü'],
                    ['Ä', 'Ö', 'Ü'],
                    mb_substr($word, 1, 1)
                )
            )
        );

        // Wörter mit weniger als 3 Buchstaben
        if (strlen($word) <= 3) {
            return false;
        }

        // Wörter welche nicht erlaubt sind
        $not = [
            'ab',
            'aus',
            'von',
            'an',
            'auf',
            'außer',
            'bei',
            'hinter',
            'in',
            'neben',
            'über',
            'vor',
            'entlang',
            'innerhalb',
            'längs',
            'an',
            'auf',
            'bis',
            'durch',
            'hinter',
            'in',
            'nach',
            'nachdem',
            'zu',
            'mit',
            'der',
            'dem',
            'den',
            'die',
            'das',
            'dass',
            'daß',
            'ein',
            'eine',
            'einer',
            'eines',
            'einem',
            'einen',
            'wegen',
            'zufolge',
            'in',
            'auf',
            'unter',
            'über',
            'da',
            'dort',
            'heute',
            'darum',
            'deshalb',
            'warum',
            'weshalb',
            'weswegen',
            'oben',
            'ausgerechnet',
            'nur',
            'wo',
            'wann',
            'wie',
            'wieso',
            'halt',
            'übrigens',
            'daran',
            'dran',
            'woran',
            'darüber',
            'drüber',
            'hierüber',
            'worüber',
            'gern',
            'ich',
            'du',
            'er',
            'sie',
            'es',
            'wir',
            'mir',
            'euch',
            'sie',
            'uns',
            'eure',
            'deren',
            'etwas',
            'jedermann',
            ' paar',
            'was',
            'denen',
            'alle',
            'man',
            'wer',
            'für',
            'um',
            'binnen',
            'seit',
            'während',
            'angesichts',
            'anlässlich',
            'aufgrund',
            'behufs',
            'dank',
            'gemäß',
            'infolge',
            'kraft',
            'laut',
            'mangels',
            'ob',
            'seitens',
            'seitdem',
            'trotz',
            'unbeschadet',
            'ungeachtet',
            'vermöge',
            'zwecks',
            'zu',
            'zur',
            'zum',
            'vergebens',
            'fast',
            'zwar',
            'sehr',
            'recht',
            'überaus',
            'folglich',
            'ja',
            'halt',
            'eh',
            'wohl',
            'erstens',
            'zweitens',
            'drittens',
            'sogar',
            'bereits',
            'bedauerlicherweise',
            'leider',
            'sicher',
            'sicherlich',
            'vielleicht',
            'viel',
            'viele',
            'vieles',
            'abzüglich',
            'exklusive',
            'inklusive',
            'mit',
            'nebst',
            'ohne',
            ' statt',
            'anstatt',
            'wider',
            'wieder',
            'zuwider',
            'obwohl',
            'wenn',
            'falls',
            'weil',
            'bevor',
            'als',
            'indem',
            'und',
            'weder',
            'noch',
            'allerdings',
            'aber',
            'entweder',
            'oder',
            'heißt',
            'nämlich',
            'ehe',
            'gleich',
            'woher',
            'wohin',
            'wodurch',
            'soviel',
            'sowie',
            'sooft',
            'denn',
            'nun',
            'sobald',
            'sodass',
            'so',
            'damit',
            'wird',
            'werden',
            'hat',
            'habe',
            'haben',
            'hatte',
            'hatten',
            'doch',
            'jedoch',
            'kann',
            'können',
            'konnte',
            'konnten',
            'soll',
            'sollte',
            'sollten',
            'dazu',
            'ohnehin',
            'muss',
            'war',
            'waren',
            'machen',
            ' dann',
            'derzeit',
            'beim',
            'auch',
            'will',
            'wollen',
            'schon',
            'eher',
            'lassen',
            'läßt',
            'lässt',
            'ließ',
            'lies',
            'dürfen',
            'darf',
            ' gibt',
            'geben',
            'gab',
            'gaben',
            'zuletzt',
            'also',
            'davon'
        ];

        $_word = strtolower($word);

        if (in_array($_word, $not)) {
            return false;
        }

        /*  *gegen*  */
        if (str_contains($word, 'gegen')) {
            return false;
        }

        /* search*  */
        $beginings = [
            'außer',
            'ober',
            'unter,	welch',
            'kein',
            'hier',
            'nicht',
            'jede',
            'manch',
            'dies',
            'jene',
            'jemand',
            'halb',
            'irgend',
            'nirgend',
            'indes',
            'solang',
            'beide',
            'erste',
            'zweite',
            'dritte',
            'vierte',
            'fünfte',
            'sechste',
            'siebte',
            'achte',
            'neunte',
            'zehnte',
            'elfte',
            'zwölfte',
            'vermittels',
            'einig',
            'betreff',
            'ihr',
            'ihn',
            'mein',
            'sein',
            'unser',
            'euer',
            'dein',
            'niemand',
            'mittels',
            'sonder',
            'manch',
            'mein',
            'wurde',
            'musste',
            'wollte',
            'durfte',
            'machte'
        ];

        foreach ($beginings as $search) {
            $pos = strpos($_word, $search);

            if ($pos === false) {
                continue;
            }

            if ($pos == 0) {
                return false;
            }
        }

        /* *search  */
        $endings = [
            'mal',
            'sofern',
            'soweit',
            'samt',
            'einander',
            'schließlich',
            'züglich',
            'weit',
            'zwischen',
            'mitten',
            'jenige',
            'selbe'
        ];

        foreach ($endings as $search) {
            if (preg_match('/(.*?)' . $search . '$/i', $word)) {
                return false;
            }
        }

        return true;
    }
}
