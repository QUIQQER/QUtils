<?php

/**
 * This file contains QUI\Utils\StringHelper
 */

namespace QUI\Utils;

use QUI;

mb_internal_encoding('UTF-8');

if (!function_exists('fnmatch')) {
    define('FNM_PATHNAME', 1);
    define('FNM_NOESCAPE', 2);
    define('FNM_PERIOD', 4);
    define('FNM_CASEFOLD', 16);
}

/**
 * Helper for string handling
 *
 * @author  www.pcsg.de (Henning Leutz
 * @package com.pcsg.qutils
 *
 * @todo    doku translation
 */
class StringHelper
{
    /**
     * internal string param
     *
     * @var string
     */
    public $string;

    /**
     * Constructor
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->string = (string)$string;
    }

    /**
     * Wandelt JavaScript Strings für PHP in richtige Strings um
     *
     * @param string|boolean $value
     *
     * @return string
     */
    public static function JSString($value)
    {
        if (\is_string($value)) {
            return $value;
        }

        return (string)$value;
    }

    /**
     * Verinfachtes Pathinfo
     *
     * @param string $path - path to file
     * @param integer|bool $options - PATHINFO_DIRNAME, PATHINFO_BASENAME, PATHINFO_EXTENSION
     *
     * @return array|string
     * @throws \QUI\Exception
     */
    public static function pathinfo($path, $options = false)
    {
        if (!\file_exists($path)) {
            throw new QUI\Exception('File '.$path.' not exists');
        }

        $info = \pathinfo($path);

        if ($options == PATHINFO_DIRNAME) {
            return $info['dirname'];
        }

        if ($options == PATHINFO_BASENAME) {
            return $info['basename'];
        }

        if ($options == PATHINFO_EXTENSION) {
            return $info['extension'];
        }

        if ($options == PATHINFO_FILENAME) {
            return $info['filename'];
        }

        return $info;
    }

    /**
     * Entfernt doppelte Slashes und macht einen draus
     * // -> /
     * /// -> /
     *
     * @param string $path
     *
     * @return string
     */
    public static function replaceDblSlashes($path)
    {
        return \preg_replace('/[\/]{2,}/', "/", $path);
    }

    /**
     * Entfernt Zeilenumbrüche
     *
     * @param string $text
     * @param string $replace - Mit was ersetzt werden soll
     *
     * @return string
     */
    public static function removeLineBreaks($text, $replace = '')
    {
        return \str_replace(
            ["\r\n", "\n\r", "\n", "\r"],
            $replace,
            $text
        );
    }

    /**
     * Löscht doppelte hintereinander folgende Zeichen in einem String
     *
     * @param string $str
     *
     * @return string
     */
    public static function removeDblSigns($str)
    {
        $_str = $str;
        $_str = \utf8_decode($_str);

        for ($i = 0, $len = \mb_strlen($str); $i < $len; $i++) {
            $char = \mb_substr($str, $i, 1);

            if (empty($char)) {
                continue;
            }

            $char = \addslashes($char);
            $char = \preg_quote($char);

            if ($char === '#') {
                $char = '\\'.$char;
            }

            $regex = '#(['.$char.']){2,}#';

            $_str = \preg_replace($regex, "$1", $_str);
        }

        $_str = \utf8_encode($_str);

        return $_str;
    }

    /**
     * Entfernt den letzten Slash am Ende, wenn das letzte Zeichen ein Slash ist
     *
     * @param string $str
     *
     * @return string
     */
    public static function removeLastSlash($str)
    {
        return \preg_replace(
            '/\/($|\?|\#)/U',
            '\1',
            $str
        );
    }

    /**
     * Erstes Zeichen eines Wortes gross schreiben alle anderen klein
     *
     * @param string $str
     *
     * @return string
     */
    public static function firstToUpper($str)
    {
        return \ucfirst(self::toLower($str));
    }

    /**
     * Schreibt den String klein
     *
     * @param string $string
     *
     * @return string
     */
    public static function toLower($string)
    {
        return \mb_strtolower($string);
    }

    /**
     * Schreibt den String gross
     *
     * @param string $string
     *
     * @return string
     */
    public static function toUpper($string)
    {
        return \mb_strtoupper($string);
    }

    /**
     * Prüft ob der String ein Echter UTF8 String ist
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function isValidUTF8($str)
    {
        $test1 = false;
        $test2 = false;

        if (\preg_match('%^(?:
                  [\x09\x0A\x0D\x20-\x7E]            # ASCII
                   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
                | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
                | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
                | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
                )*$%xs', $str)
        ) {
            $test1 = true;
        }

        if (!((bool)\preg_match(
            '~[\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF\xC0\xC1]~ms',
            $str
        ))
        ) {
            $test2 = true;
        }

        if ($test1 && $test2) {
            return true;
        }

        return false;
    }

    /**
     * Gibt einen String als UTF8 String zurück
     *
     * @param string $str
     *
     * @return string
     */
    public static function toUTF8($str)
    {
        if (!self::isValidUTF8($str)) {
            return \utf8_encode($str);
        }

        return $str;
    }

    /**
     * Erster Satz bekommen
     *
     * @param string $text
     *
     * @return string
     */
    public static function sentence($text)
    {
        $d = \strpos($text, '.');
        $a = \strpos($text, '!');
        $q = \strpos($text, '?');

        if ($d === false && $a === false && $q === false) {
            return '';
        }

        $_min_vars = [];

        if ($d !== false) {
            $_min_vars[] = $d;
        }

        if ($a !== false) {
            $_min_vars[] = $a;
        }

        if ($q !== false) {
            $_min_vars[] = $q;
        }

        return \trim(
            \substr($text, 0, \min($_min_vars) + 1)
        );
    }

    /**
     * Parset einen String zu einem richtigen Float Wert
     * From php.net
     *
     * @param string $str
     *
     * @return float
     */
    public static function parseFloat($str)
    {
        if (\is_float($str)) {
            return $str;
        }

        if (empty($str)) {
            return 0;
        }
        // @todo lokaliesierung richtig prüfen localeconv()
        if (\strstr((string)$str, ",")) {
            $str = \str_replace(".", "", (string)$str);
            $str = \str_replace(",", ".", (string)$str);
        }

        $minus = false;

        if ($str[0] == '-' || $str < 0) {
            $minus = true;
        }

        if (\preg_match("#([0-9\.]+)#", $str, $match)) {
            $result = \floatval($match[0]);
        } else {
            $result = \floatval($str);
        }


        if ($minus && $result > 0) {
            return (-1) * $result;
        }

        return $result;
    }

    /**
     * Wandelt eine Zahl in das passende Format für eine Datenbank um
     *
     * @param string $value
     *
     * @return number
     */
    public static function number2db($value)
    {
        $larr   = \localeconv();
        $search = [
            $larr['decimal_point'],
            $larr['mon_decimal_point'],
            $larr['thousands_sep'],
            $larr['mon_thousands_sep'],
            $larr['currency_symbol'],
            $larr['int_curr_symbol']
        ];

        $replace = ['.', '.', '', '', '', ''];

        return \str_replace($search, $replace, $value);
    }

    /**
     * Enter description here...
     *
     * @param array $tags
     * @param integer $start
     * @param integer $min
     *
     * @return string
     */
    public static function tagCloud($tags, $start = 26, $min = 10)
    {
        if (!\is_array($tags)) {
            $tags = [];
        }

        for ($i = 0, $len = \count($tags); $i < $len; $i++) {
            $tags[$i]['count'] = $i;
        }

        \shuffle($tags);

        $str = '';

        foreach ($tags as $entry) {
            $size = $start - $entry['count'];

            if ($min > $size) {
                $size = $min;
            }

            $str .= '<a href="'.$entry['url'].'" style="font-size: '.$size.'px">'.$entry['tag'].'</a> ';
        }

        return $str;
    }

    /**
     * Einzelnen Attribute einer URL bekommen
     *
     * @param string $url - ?id=1&project=demo
     *
     * @return array
     */
    public static function getUrlAttributes($url)
    {
        $url = \str_replace('&amp;', '&', $url);
        $url = \explode('?', $url);
        $att = [];

        if (!isset($url[1])) {
            return $att;
        }

        $att_ = \explode('&', $url[1]);

        foreach ($att_ as $a) {
            $item          = \explode('=', $a);
            $att[$item[0]] = $item[1];
        }

        return $att;
    }


    /**
     * Turns a URL parsed via parse_url back into a string.
     *
     * @param $parsedUrl
     *
     * @return string
     *
     * @author "thomas at gielfeldt dot com" on php.net (https://www.php.net/manual/de/function.parse-url.php#106731)
     */
    public static function unparseUrl($parsedUrl)
    {
        $scheme   = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host     = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port     = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user     = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass     = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query    = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
    
    
    /**
     * Gibt die Attribute eines HTML Strings zurück
     *
     * @param string $html - <img * />
     *
     * @return array
     */
    public static function getHTMLAttributes($html)
    {
        $cleaned = \preg_replace('/\s+=\s+/', '=', $html);

        \preg_match_all('/(?:^|\s)([\w|-]+)="([^">]+)"/', $cleaned, $qatts);
        \preg_match_all('/(?:^|\s)([\w|-]+)=([^"\s>]+)/', $cleaned, $patts);
        $allatts = \array_merge($patts[1], $qatts[1]);
        $allvals = \array_merge($patts[2], $qatts[2]);

        $attributes = [];

        for ($i = 0; $i <= \count($allatts) - 1; $i++) {
            $attributes[$allatts[$i]] = $allvals[$i];
        }

        return $attributes;
    }

    /**
     * Gibt die Attribute eines HTML Styles zurück
     *
     * @param string $style - "width:200px; height:200px"
     *
     * @return array
     */
    public static function splitStyleAttributes($style)
    {
        $attributes = [];
        $style      = \trim($style);
        $style      = \explode(';', $style);

        foreach ($style as $att) {
            $att_ = \explode(':', $att);

            if (!isset($att_[1])) {
                continue;
            }

            $key = self::toLower(\trim($att_[0]));
            $val = self::toLower(\trim($att_[1]));

            $attributes[$key] = $val;
        }

        return $attributes;
    }

    /**
     * Replace the last occurrences of the search string with the replacement string
     *
     * @param string $search
     * @param string $replace
     * @param string $string
     *
     * @return string
     */
    public static function replaceLast($search, $replace, $string)
    {
        if (\strpos($string, $search) === false) {
            return $string;
        }

        return \substr_replace(
            $string,
            $replace,
            \strrpos($string, $search),
            \strlen($search)
        );
    }

    /**
     * Match String against a pattern
     *
     * @param string $pattern - The shell wildcard pattern.
     * @param string $string - The tested string.
     * @param integer $flags - The value of flags can be any combination of the following flags,
     *                         joined with the binary OR (|) operator.
     *                         ( http://php.net/manual/de/function.fnmatch.php )
     *
     * @return boolean
     */
    public static function match($pattern, $string, $flags = 0)
    {
        if (\function_exists('fnmatch')) {
            return \fnmatch($pattern, $string, $flags);
        }

        // solution if fnmatch doesn't exist
        // found on http://php.net/manual/de/function.fnmatch.php
        $modifiers  = null;
        $transforms = [
            '\*'   => '.*',
            '\?'   => '.',
            '\[\!' => '[^',
            '\['   => '[',
            '\]'   => ']',
            '\.'   => '\.',
            '\\'   => '\\\\'
        ];

        // Forward slash in string must be in pattern:
        if ($flags & FNM_PATHNAME) {
            $transforms['\*'] = '[^/]*';
        }

        // Back slash should not be escaped:
        if ($flags & FNM_NOESCAPE) {
            unset($transforms['\\']);
        }

        // Perform case insensitive match:
        if ($flags & FNM_CASEFOLD) {
            $modifiers .= 'i';
        }

        // Period at start must be the same as pattern:
        if ($flags & FNM_PERIOD) {
            if (\strpos($string, '.') === 0 && \strpos($pattern, '.') !== 0) {
                return false;
            }
        }

        $pattern = '#^'
                   .\strtr(\preg_quote($pattern, '#'), $transforms)
                   .'$#'
                   .$modifiers;

        return (boolean)\preg_match($pattern, $string);
    }

    /**
     * Replaces a string in a string from right to left
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function strReplaceFromEnd($search, $replace, $subject)
    {
        $pos = \strrpos($subject, $search);

        if ($pos !== false) {
            $subject = \substr_replace($subject, $replace, $pos, \strlen($search));
        }

        return $subject;
    }
}
