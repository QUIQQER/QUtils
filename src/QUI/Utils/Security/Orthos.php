<?php

/**
 * This file contains the QUI\Utils\Security\Orthos
 */

namespace QUI\Utils\Security;

use QUI;
use QUI\Utils\StringHelper;
use QUI\Utils\Text\BBCode;

use function checkdate;
use function class_exists;
use function escapeshellarg;
use function escapeshellcmd;
use function explode;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function implode;
use function is_array;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_string;
use function microtime;
use function mt_rand;
use function mt_srand;
use function preg_match;
use function preg_replace;
use function str_replace;
use function strip_tags;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Orthos - Security class
 *
 * Has different methods in order to examine variables on their correctness
 * Should be used to validate user input
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 */
class Orthos
{
    /**
     * Cleans a string from all possible malicious code
     *
     * @param string|null $str
     *
     * @return string
     */
    public static function clear(?string $str): string
    {
        if ($str === null) {
            return '';
        }

        $str = self::removeHTML($str);
        $str = self::clearFormRequest($str);
        $str = self::clearPath($str);

        $str = htmlspecialchars($str);

        return $str;
    }

    /**
     * Remove all none characters in the string.
     * none characters are no a-z A-z or 0-9
     *
     * @param string $str
     * @param array $allowedList - list of allowed signs
     *
     * @return string
     */
    public static function clearNoneCharacters($str = '', $allowedList = [])
    {
        $chars = 'a-zA-Z0-9';

        if (is_array($allowedList)) {
            $chars .= implode($allowedList);
        }

        return preg_replace("/[^{$chars}]/", "", $str);
    }

    /**
     * Cleans an array from all possible malicious code
     *
     * @param array|mixed $data
     *
     * @return array
     */
    public static function clearArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $cleanData = [];

        foreach ($data as $key => $str) {
            if (is_array($str)) {
                $cleanData[$key] = self::clearArray($str);
                continue;
            }

            if (is_string($str)) {
                $cleanData[$key] = self::clear($str);
                continue;
            }

            if (is_numeric($str) || is_bool($str)) {
                $cleanData[$key] = $str;
                continue;
            }

            if ($str === null) {
                $cleanData[$key] = null;
                continue;
            }

            unset($cleanData[$key]);
        }

        return $cleanData;
    }

    /**
     * Clears a path of possible changes to the path
     *
     * @param string|array $path
     *
     * @return array|string|string[]
     */
    public static function clearPath($path)
    {
        $path = str_replace('\\', '', $path);

        return str_replace(['../', '..'], '', $path);
    }

    /**
     * cleans a file name
     * characters that may become dangerous for file names, will be removed
     *
     * @param $filename
     * @return array|string|string[]
     */
    public static function clearFilename($filename)
    {
        return str_replace(
            [" ", '"', "'", "&", "/", "\\", "?", "#"],
            '_',
            $filename
        );
    }

    /**
     * Removes HTML from the text
     *
     * @param string|null $text
     *
     * @return string
     */
    public static function removeHTML(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        return strip_tags($text);
    }

    /**
     * Cleans a MySQL command string from malicious code
     *
     * If you are using this function to build SQL statements,
     * you are strongly recommended to use PDO::prepare() to prepare
     * SQL statements with bound parameters instead of using PDO::quote()
     * to interpolate user input into an SQL statement. Prepared statements with
     * bound parameters are not only more portable, more convenient, immune to SQL injection,
     * but are often much faster to execute than interpolated queries,
     * as both the server and client side can cache a compiled form of the query.
     *
     * @param string $str - Command
     * @param boolean $escape - Escape the String (true or false}
     *
     * @return string
     *
     * @deprecated use PDO::quote (QUI::getPDO()->quote())
     */
    public static function clearMySQL(string $str, bool $escape = true): string
    {
        if ($escape && class_exists('QUI')) {
            $str = QUI::getPDO()->quote($str);
        }

        return $str;
    }

    /**
     * Remove signs which can cause sql injections
     * This method should only be used for table names in order, group, from, select
     *
     * @param $str
     * @return mixed|string
     */
    public static function cleanupDatabaseFieldName($str)
    {
        if (empty($str)) {
            return '';
        }

        $str = preg_replace('/[^0-9,a-z,A-Z$_.]/i', '', $str);
        $str = str_replace('..', '', $str);
        $str = trim($str);
        $str = trim($str, '`');

        $str = str_replace('.', '`.`', $str);
        $str = '`' . $str . '`';

        return $str;
    }

    /**
     * Cleans a shell command string from malicious code
     *
     * Do not use for commands with special characters
     * (for this, clean individual arguments with Orthos::clearShellArg()).
     *
     * @param string $str - Command
     *
     * @return string
     */
    public static function clearShell(string $str): string
    {
        return escapeshellcmd($str);
    }

    /**
     * Encloses a shell argument in single quotes and escapes them
     *
     * @param string $str - Shell-Argument
     * @return string
     */
    public static function clearShellArg(string $str): string
    {
        return escapeshellarg($str);
    }

    /**
     * Parse a string, bool, float to an integer value
     *
     * @param string|bool|float $str
     *
     * @return integer
     */
    public static function parseInt($str): int
    {
        return (int)$str;
    }

    /**
     * Cleans out "bad" HTML
     * You can use this for example for wiki text
     *
     * @param string $str
     *
     * @return string
     */
    public static function cleanHTML(string $str): string
    {
        $BBCode = new BBCode();

        $str = $BBCode->parseToBBCode($str);
        $str = $BBCode->parseToHTML($str);

        return $str;
    }

    /**
     * Checks date parts for correctness
     * If correct, $val returns otherwise 0
     *
     * @param integer|string $val
     * @param string $type - DAY | MONTH | YEAR
     *
     * @return integer
     */
    public static function date($val, string $type = 'DAY'): int
    {
        if ($type == 'MONTH') {
            $val = self::parseInt($val);

            // Wenn Monat nicht zwischen 1 und 12 liegt
            if ($val < 1 || $val > 12) {
                return 0;
            }

            return $val;
        }


        if ($type == 'YEAR') {
            return self::parseInt($val);
        }


        $val = self::parseInt($val);

        // Wenn Tag nicht zwischen 1 und 31 liegt
        if ($val < 1 || $val > 31) {
            return 0;
        }

        return $val;
    }

    /**
     * Checks a date for correctness
     *
     * @param mixed $day
     * @param mixed $month
     * @param mixed $year
     *
     * @return boolean
     */
    public static function checkdate($day, $month, $year): bool
    {
        if (!is_int($day)) {
            return false;
        }

        if (!is_int($month)) {
            return false;
        }

        if (!is_int($year)) {
            return false;
        }

        return checkdate($month, $day, $year);
    }

    /**
     * use \QUI\Utils\StringHelper::removeLineBreaks
     *
     * @param string|array $text
     *
     * @return string|array
     * @see        \QUI\Utils\StringHelper::removeLineBreaks
     * @deprecated use \QUI\Utils\StringHelper::removeLineBreaks
     *
     */
    public static function removeLineBreaks($text): string
    {
        return StringHelper::removeLineBreaks($text, '');
    }

    /**
     * Checks a mail address for syntax
     *
     * @param string $email - Mail Adresse
     *
     * @return boolean
     */
    public static function checkMailSyntax($email): bool
    {
        return preg_match(
            '/^([A-Za-z0-9\.\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]){1,64}\@{1}([A-Za-z0-9\.\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~]){1,248}\.{1}([a-z]){2,6}$/i',
            $email
        );
    }

    /**
     * Checks a MySQL datetime for syntax
     *
     * @param string $date - 0000-00-00 00:00:00
     *
     * @return boolean
     */
    public static function checkMySqlDatetimeSyntax($date): bool
    {
        // Nur Zahlen erlaubt
        if (preg_match('/[^0-9- :]/i', $date)) {
            return false;
        }

        // Syntaxprüfung
        if (!preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $date)) {
            return false;
        }

        return true;
    }

    /**
     * Checks a MySQL timestamp for syntax
     *
     * @param string $date - 0000-00-00 00:00:00
     *
     * @return boolean
     */
    public static function checkMySqlTimestampSyntax($date): bool
    {
        return self::checkMySqlDatetimeSyntax($date);
    }

    /**
     * Checks a MySQL date for syntax
     *
     * @param string $date - 0000-00-00
     *
     * @return boolean
     */
    public static function checkMySqlDateSyntax($date): bool
    {
        // Nur Zahlen erlaubt
        if (preg_match('/[^0-9- :]/i', $date)) {
            return false;
        }

        // Syntaxprüfung
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
            return false;
        }

        return true;
    }

    /**
     * Generates a random string
     *
     * @param integer $length - Length of the password
     *
     * @return string
     */
    public static function getPassword($length = 10): string
    {
        if (!is_int($length)) {
            $length = 10;
        }

        $newPass = "";
        $string  = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789()[]{}?!$%&/=*+~,.;:<>-_";

        mt_srand((int)(microtime() * 1000000));

        for ($i = 1; $i <= $length; $i++) {
            $newPass .= substr($string, mt_rand(0, strlen($string) - 1), 1);
        }

        return $newPass;
    }

    /**
     * Checks whether the mail address is a disposable spam mail address.
     *
     * @param string $mail - E-Mail Adresse
     *
     * @return boolean
     */
    public static function isSpamMail($mail): bool
    {
        $split = explode('@', $mail);

        $adresses = [
            'anonbox.net',
            'bumpymail.com',
            'centermail.com',
            'centermail.net',
            'discardmail.com',
            'emailias.com',
            'jetable.net',
            'mailexpire.com',
            'mailinator.com',
            'messagebeamer.de',
            'mytrashmail.com',
            'trash-mail.de',
            'trash-mail.com',
            'trashmail.net',
            'pookmail.com',
            'nervmich.net',
            'netzidiot.de',
            'nurfuerspam.de',
            'mail.net',
            'privacy.net',
            'punkass.com',
            'sneakemail.com',
            'sofort-mail.de',
            'spamex.com',
            'spamgourmet.com',
            'spamhole.com',
            'spaminator.de',
            'spammotel.com',
            'spamtrail.com',
            'temporaryinbox.com',
            'put2.net',
            'senseless-entertainment.com',
            'dontsendmespam.de',
            'spam.la',
            'spaml.de',
            'spambob.com',
            'kasmail.com',
            'dumpmail.de',
            'dodgeit.com',
            'fastacura.com',
            'fastchevy.com',
            'fastchrysler.com',
            'fastkawasaki.com',
            'fastmazda.com',
            'fastmitsubishi.com',
            'fastnissan.com',
            'fastsubaru.com',
            'fastsuzuki.com',
            'fasttoyota.com',
            'fastyamaha.com',
            'nospamfor.us',
            'nospam4.us',
            'trashdevil.de',
            'trashdevil.com',
            'spoofmail.de',
            'fivemail.de',
            'giantmail.de'
        ];

        if (!isset($split[1])) {
            return false;
        }

        foreach ($adresses as $entry) {
            if (strpos($split[1], $entry) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes all characters from a request that could be used for an XSS.
     *
     * @param string|array $value
     *
     * @return string|array
     */
    public static function clearFormRequest($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $entry) {
                $value[$key] = self::clearFormRequest($entry); // htmlspecialchars_decode($entry);
            }
        } else {
            if (!is_string($value)) {
                return '';
            }

            $value = htmlspecialchars_decode($value);
        }
        // alle zeichen und HEX codes werden mit leer ersetzt
        $value = str_replace(
            [
                '<',
                '%3C',
                '>',
                '%3E',
                '"',
                '%22',
                '\\',
                '%5C',
                '\'',
                '%27',
            ],
            '',
            $value
        );

        return $value;
    }

    /**
     * Encodes a string for safe and unambiguous url use
     *
     * @param string $str
     * @param string $replace - replacement character for unsafe / ambiguous characters
     * @return string - filtered string
     */
    public static function urlEncodeString($str, $replace = "-"): string
    {
        if (!is_string($replace)) {
            $replace = "-";
        }

        // special reserved url characters
        // @see https://de.wikipedia.org/wiki/URL-Encoding#Relevante_ASCII-Zeichen_in_.25-Darstellung
        $reservedChars = [
            "!",
            "#",
            "$",
            "%",
            "&",
            "'",
            "(",
            ")",
            "*",
            "+",
            ",",
            "/",
            ":",
            ";",
            "=",
            "?",
            "@",
            "[",
            "]"
        ];

        // replace special chars with replacement character
        $str = str_replace($reservedChars, $replace, $str);

        // filter non-letters and non-numbers and non-allowed url characters
        $str = preg_replace('#[^\p{L}\d\-_.~]+#iu', $replace, $str);

        // trim outer and double replacement characters
        $str = trim($str, $replace);

        // reduce multiple replacement chars in a row
        $str = preg_replace('#\\' . $replace . '{2,}#i', $replace, $str);

        return StringHelper::toLower($str);
    }

    /**
     * Escape HTML
     *
     * @param string $str
     * @return string
     */
    public static function escapeHTML(string $str): string
    {
        return htmlspecialchars($str);
    }
}
