<?php

/**
 * This file contains QUI\Utils\StringHelper
 */

namespace QUI\Utils;

use DateTimeInterface;
use IntlDateFormatter;
use InvalidArgumentException;
use QUI;
use QUI\Exception;

use function addslashes;
use function array_merge;
use function count;
use function explode;
use function file_exists;
use function floatval;
use function fnmatch;
use function function_exists;
use function is_array;
use function is_float;
use function is_string;
use function localeconv;
use function mb_strlen;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function min;
use function pathinfo;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function shuffle;
use function str_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strstr;
use function strtr;
use function substr;
use function substr_replace;
use function trim;
use function ucfirst;
use function utf8_decode;
use function utf8_encode;

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
 */
class StringHelper
{
    /**
     * internal string param
     *
     * @var string
     */
    public string $string;

    /**
     * Constructor
     *
     * @param string $string
     */
    public function __construct(string $string)
    {
        $this->string = $string;
    }

    /**
     * Converts JavaScript strings to real strings for PHP
     *
     * @param string|boolean $value
     *
     * @return string
     */
    public static function JSString($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return (string)$value;
    }

    /**
     * Simplified Pathinfo
     *
     * @param string $path - path to file
     * @param integer|bool $options - PATHINFO_DIRNAME, PATHINFO_BASENAME, PATHINFO_EXTENSION
     *
     * @return array|string
     * @throws Exception
     */
    public static function pathinfo($path, $options = false)
    {
        if (!file_exists($path)) {
            throw new Exception('File ' . $path . ' not exists');
        }

        $info = pathinfo($path);

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
     * Removes duplicate slashes and makes it one
     * // -> /
     * /// -> /
     *
     * @param string $path
     *
     * @return string
     */
    public static function replaceDblSlashes(string $path): string
    {
        return preg_replace('/[\/]{2,}/', "/", $path);
    }

    /**
     * Removes line breaks
     *
     * @param string $text
     * @param string $replace - With what should be replaced
     *
     * @return string
     */
    public static function removeLineBreaks(string $text, string $replace = ''): string
    {
        return str_replace(
            ["\r\n", "\n\r", "\n", "\r"],
            $replace,
            $text
        );
    }

    /**
     * Deletes duplicate consecutive characters in a string
     *
     * @param string $str
     *
     * @return string
     */
    public static function removeDblSigns(string $str): string
    {
        $_str = $str;
        $_str = utf8_decode($_str);

        for ($i = 0, $len = mb_strlen($str); $i < $len; $i++) {
            $char = mb_substr($str, $i, 1);

            if (empty($char)) {
                continue;
            }

            $char = addslashes($char);
            $char = preg_quote($char);

            if ($char === '#') {
                $char = '\\' . $char;
            }

            $regex = '#([' . $char . ']){2,}#';

            $_str = preg_replace($regex, "$1", $_str);
        }

        $_str = utf8_encode($_str);

        return $_str;
    }

    /**
     * Removes the last slash at the end if the last character is a slash
     *
     * @param string $str
     *
     * @return string
     */
    public static function removeLastSlash(string $str): string
    {
        return preg_replace(
            '/\/($|\?|\#)/U',
            '\1',
            $str
        );
    }

    /**
     * Capitalize first character of a word all others lowercase
     *
     * @param string $str
     *
     * @return string
     */
    public static function firstToUpper(string $str): string
    {
        return ucfirst(self::toLower($str));
    }

    /**
     * Writes the string small
     *
     * @param string $string
     *
     * @return string
     */
    public static function toLower(string $string): string
    {
        return mb_strtolower($string);
    }

    /**
     * Writes the string in capital letters
     *
     * @param string $string
     *
     * @return string
     */
    public static function toUpper(string $string): string
    {
        return mb_strtoupper($string);
    }

    /**
     * Checks if the string is a real UTF8 string
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function isValidUTF8(string $str): bool
    {
        $test1 = false;
        $test2 = false;

        if (preg_match(
            '%^(?:
                  [\x09\x0A\x0D\x20-\x7E]            # ASCII
                   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
                | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
                | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
                | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
                )*$%xs',
            $str
        )
        ) {
            $test1 = true;
        }

        if (!((bool)preg_match(
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
     * Returns a string as UTF8 string
     *
     * @param string $str
     *
     * @return string
     */
    public static function toUTF8(string $str): string
    {
        if (!self::isValidUTF8($str)) {
            return utf8_encode($str);
        }

        return $str;
    }

    /**
     * Get first set
     *
     * @param string $text
     *
     * @return string
     */
    public static function sentence(string $text): string
    {
        $d = strpos($text, '.');
        $a = strpos($text, '!');
        $q = strpos($text, '?');

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

        return trim(
            substr($text, 0, min($_min_vars) + 1)
        );
    }

    /**
     * Parse a string to a real float value
     * From php.net
     *
     * @param string|float|mixed $str
     *
     * @return float
     */
    public static function parseFloat($str)
    {
        if (is_float($str)) {
            return $str;
        }

        if (empty($str)) {
            return 0;
        }
        // @todo lokaliesierung richtig prüfen localeconv()
        if (strstr((string)$str, ",")) {
            $str = str_replace(".", "", (string)$str);
            $str = str_replace(",", ".", (string)$str);
        }

        $minus = false;

        if ($str[0] == '-' || $str < 0) {
            $minus = true;
        }

        if (preg_match("#([0-9\.]+)#", $str, $match)) {
            $result = floatval($match[0]);
        } else {
            $result = floatval($str);
        }


        if ($minus && $result > 0) {
            return (-1) * $result;
        }

        return $result;
    }

    /**
     * Converts a number into the appropriate format for a database
     *
     * @param mixed $value
     *
     * @return array|string|string[]
     */
    public static function number2db($value)
    {
        $larr   = localeconv();
        $search = [
            $larr['decimal_point'],
            $larr['mon_decimal_point'],
            $larr['thousands_sep'],
            $larr['mon_thousands_sep'],
            $larr['currency_symbol'],
            $larr['int_curr_symbol']
        ];

        $replace = ['.', '.', '', '', '', ''];

        return str_replace($search, $replace, $value);
    }

    /**
     * Enter description here...
     * @param array $tags
     * @param integer $start
     * @param integer $min
     *
     * @return string
     * @deprecated
     *
     */
    public static function tagCloud($tags, $start = 26, $min = 10)
    {
        if (!is_array($tags)) {
            $tags = [];
        }

        for ($i = 0, $len = count($tags); $i < $len; $i++) {
            $tags[$i]['count'] = $i;
        }

        shuffle($tags);

        $str = '';

        foreach ($tags as $entry) {
            $size = $start - $entry['count'];

            if ($min > $size) {
                $size = $min;
            }

            $str .= '<a href="' . $entry['url'] . '" style="font-size: ' . $size . 'px">' . $entry['tag'] . '</a> ';
        }

        return $str;
    }

    /**
     * Get individual attributes of a URL
     *
     * @param string $url - ?id=1&project=demo
     *
     * @return array
     */
    public static function getUrlAttributes(string $url): array
    {
        $url = str_replace('&amp;', '&', $url);
        $url = explode('?', $url);
        $att = [];

        if (!isset($url[1])) {
            return $att;
        }

        $att_ = explode('&', $url[1]);

        foreach ($att_ as $a) {
            $item          = explode('=', $a);
            $att[$item[0]] = $item[1] ?? null;
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
    public static function unparseUrl($parsedUrl): string
    {
        $scheme   = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host     = $parsedUrl['host'] ?? '';
        $port     = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user     = $parsedUrl['user'] ?? '';
        $pass     = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parsedUrl['path'] ?? '';
        $query    = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }


    /**
     * Returns the attributes of an HTML string
     *
     * @param string $html - <img * />
     *
     * @return array
     */
    public static function getHTMLAttributes(string $html): array
    {
        $cleaned = preg_replace('/\s+=\s+/', '=', $html);

        preg_match_all('/(?:^|\s)([\w|-]+)="([^">]+)"/', $cleaned, $qatts);
        preg_match_all('/(?:^|\s)([\w|-]+)=([^"\s>]+)/', $cleaned, $patts);
        $allatts = array_merge($patts[1], $qatts[1]);
        $allvals = array_merge($patts[2], $qatts[2]);

        $attributes = [];

        for ($i = 0; $i <= count($allatts) - 1; $i++) {
            $attributes[$allatts[$i]] = $allvals[$i];
        }

        return $attributes;
    }

    /**
     * Returns the attributes of an HTML style
     *
     * @param string $style - "width:200px; height:200px"
     *
     * @return array
     */
    public static function splitStyleAttributes(string $style): array
    {
        $attributes = [];
        $style      = trim($style);
        $style      = explode(';', $style);

        foreach ($style as $att) {
            $att_ = explode(':', $att);

            if (!isset($att_[1])) {
                continue;
            }

            $key = self::toLower(trim($att_[0]));
            $val = self::toLower(trim($att_[1]));

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
    public static function replaceLast(string $search, string $replace, string $string): string
    {
        if (strpos($string, $search) === false) {
            return $string;
        }

        return substr_replace(
            $string,
            $replace,
            strrpos($string, $search),
            strlen($search)
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
    public static function match(string $pattern, string $string, int $flags = 0): bool
    {
        if (function_exists('fnmatch')) {
            return fnmatch($pattern, $string, $flags);
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
            if (strpos($string, '.') === 0 && strpos($pattern, '.') !== 0) {
                return false;
            }
        }

        $pattern = '#^'
            . strtr(preg_quote($pattern, '#'), $transforms)
            . '$#'
            . $modifiers;

        return (boolean)preg_match($pattern, $string);
    }

    /**
     * Replaces a string in a string from right to left
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function strReplaceFromEnd(string $search, string $replace, string $subject): string
    {
        $pos = strrpos($subject, $search);

        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * strftime() becomes deprecated with php8 and removed in php9
     * this method is a workaround for it
     *
     * - IntlDateFormatter is used as a workaround
     *
     * thank you to: https://gist.github.com/bohwaz/42fc223031e2b2dd2585aab159a20f30
     *
     * @param string $format
     * @param ?int|?string $timestamp
     * @return string
     */
    public static function strftime(string $format, $timestamp = null): string
    {
        if (null === $timestamp) {
            $timestamp = new \DateTime;
        } elseif (is_numeric($timestamp)) {
            $timestamp = date_create('@' . $timestamp);

            if ($timestamp) {
                $timestamp->setTimezone(new \DateTimezone(date_default_timezone_get()));
            }
        } elseif (is_string($timestamp)) {
            $timestamp = date_create($timestamp);
        }

        if (!($timestamp instanceof DateTimeInterface)) {
            throw new InvalidArgumentException(
                '$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.'
            );
        }

        $locale = QUI::getLocale()->getLocalesByLang(QUI::getLocale()->getCurrent());

        $intlFormats = [
            '%a' => 'EEE',
            '%A' => 'EEEE',
            '%b' => 'MMM',
            '%B' => 'MMMM',
            '%h' => 'MMM'
        ];

        $intlFormatter = function (DateTimeInterface $timestamp, string $format) use ($intlFormats, $locale) {
            $tz        = $timestamp->getTimezone();
            $date_type = IntlDateFormatter::FULL;
            $time_type = IntlDateFormatter::FULL;
            $pattern   = '';

            // %c = Preferred date and time stamp based on locale
            // Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
            if ($format == '%c') {
                $date_type = IntlDateFormatter::LONG;
                $time_type = IntlDateFormatter::SHORT;
            } elseif ($format == '%x') {
                // %x = Preferred date representation based on locale, without the time
                // Example: 02/05/09 for February 5, 2009
                $date_type = IntlDateFormatter::SHORT;
                $time_type = IntlDateFormatter::NONE;
            } elseif ($format == '%X') {
                // Localized time format
                $date_type = IntlDateFormatter::NONE;
                $time_type = IntlDateFormatter::MEDIUM;
            } else {
                $pattern = $intlFormats[$format];
            }

            return (new IntlDateFormatter($locale, $date_type, $time_type, $tz, null, $pattern))->format($timestamp);
        };

        // Same order as https://www.php.net/manual/en/function.strftime.php
        $translationTable = [
            // Day
            '%a' => $intlFormatter,
            '%A' => $intlFormatter,
            '%d' => 'd',
            '%e' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('j'));
            },
            '%j' => function ($timestamp) {
                // Day number in year, 001 to 366
                return sprintf('%03d', $timestamp->format('z') + 1);
            },
            '%u' => 'N',
            '%w' => 'w',

            // Week
            '%U' => function ($timestamp) {
                // Number of weeks between date and first Sunday of year
                $day = new \DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },
            '%V' => 'W',
            '%W' => function ($timestamp) {
                // Number of weeks between date and first Monday of year
                $day = new \DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },

            // Month
            '%b' => $intlFormatter,
            '%B' => $intlFormatter,
            '%h' => $intlFormatter,
            '%m' => 'm',

            // Year
            '%C' => function ($timestamp) {
                // Century (-1): 19 for 20th century
                return floor($timestamp->format('Y') / 100);
            },
            '%g' => function ($timestamp) {
                return substr($timestamp->format('o'), -2);
            },
            '%G' => 'o',
            '%y' => 'y',
            '%Y' => 'Y',

            // Time
            '%H' => 'H',
            '%k' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('G'));
            },
            '%I' => 'h',
            '%l' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('g'));
            },
            '%M' => 'i',
            '%p' => 'A', // AM PM (this is reversed on purpose!)
            '%P' => 'a', // am pm
            '%r' => 'h:i:s A', // %I:%M:%S %p
            '%R' => 'H:i', // %H:%M
            '%S' => 's',
            '%T' => 'H:i:s', // %H:%M:%S
            '%X' => $intlFormatter, // Preferred time representation based on locale, without the date

            // Timezone
            '%z' => 'O',
            '%Z' => 'T',

            // Time and Date Stamps
            '%c' => $intlFormatter,
            '%D' => 'm/d/Y',
            '%F' => 'Y-m-d',
            '%s' => 'U',
            '%x' => $intlFormatter,
        ];

        $out = preg_replace_callback('/(?<!%)(%[a-zA-Z])/', function ($match) use ($translationTable, $timestamp) {
            if ($match[1] == '%n') {
                return "\n";
            } elseif ($match[1] == '%t') {
                return "\t";
            }

            if (!isset($translationTable[$match[1]])) {
                throw new InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $match[1]));
            }

            $replace = $translationTable[$match[1]];

            if (is_string($replace)) {
                return $timestamp->format($replace);
            } else {
                return $replace($timestamp, $match[1]);
            }
        }, $format);

        return str_replace('%%', '%', $out);
    }
}
