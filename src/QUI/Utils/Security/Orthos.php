<?php

/**
 * This file contains the QUI\Utils\Security\Orthos
 */

namespace QUI\Utils\Security;

use QUI\Utils\StringHelper;
use QUI\Utils\Text\BBCode;

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
     * Befreit einen String von allem möglichen Schadcode
     *
     * @param string $str
     *
     * @return string
     */
    public static function clear($str)
    {
        $str = self::removeHTML($str);
        $str = self::clearFormRequest($str);
        $str = self::clearPath($str);

        $str = \htmlspecialchars($str);

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

        if (\is_array($allowedList)) {
            $chars .= \implode($allowedList);
        }

        return \preg_replace("/[^{$chars}]/", "", $str);
    }

    /**
     * Befreit ein Array von allem möglichen Schadcode
     *
     * @param array $data
     *
     * @return array
     */
    public static function clearArray($data)
    {
        if (!\is_array($data)) {
            return [];
        }

        $cleanData = [];

        foreach ($data as $key => $str) {
            if (\is_array($data[$key])) {
                $cleanData[$key] = self::clearArray($data[$key]);
                continue;
            }

            $cleanData[$key] = self::clear($str);
        }

        return $cleanData;
    }

    /**
     * Clears a path of possible changes to the path
     *
     * @param string $path
     *
     * @return string|boolean
     */
    public static function clearPath($path)
    {
        $path = \str_replace('\\', '', $path);
        $path = \str_replace(['../', '..'], '', $path);

        return $path;
    }

    /**
     * cleans a file name
     * characters that may become dangerous for file names, will be removed
     *
     * @param $filename
     * @return mixed
     */
    public static function clearFilename($filename)
    {
        $filename = \str_replace(
            [" ", '"', "'", "&", "/", "\\", "?", "#"],
            '_',
            $filename
        );

        return $filename;
    }

    /**
     * Enfernt HTML aus dem Text
     *
     * @param string $text
     *
     * @return string
     */
    public static function removeHTML($text)
    {
        return \strip_tags($text);
    }

    /**
     * Befreit einen MySQL Command String von Schadcode
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
    public static function clearMySQL($str, $escape = true)
    {
        if ($escape && \class_exists('QUI')) {
            $str = \QUI::getPDO()->quote($str);
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

        $str = \preg_replace('/[^0-9,a-z,A-Z$_.]/i', '', $str);
        $str = \str_replace('..', '', $str);
        $str = \trim($str);
        $str = \trim($str, '`');

        $str = \str_replace('.', '`.`', $str);
        $str = '`'.$str.'`';

        return $str;
    }

    /**
     * Befreit einen Shell Command String von Schadcode
     *
     * Nicht für Befehle mit Sonderzeichen benutzen (hierfür einzelne Argumente
     * mit Orthos::clearShellArg() säubern.
     *
     * @param string $str - Command
     *
     * @return string
     */
    public static function clearShell($str)
    {
        return \escapeshellcmd($str);
    }

    /**
     * Setzt ein Shell-Argument in einfache Anführungszeichen und escaped diese
     *
     * @param string $str - Shell-Argument
     * @return string
     */
    public static function clearShellArg($str)
    {
        return \escapeshellarg($str);
    }

    /**
     * Enter description here...
     *
     * @param string $str
     *
     * @return integer
     */
    public static function parseInt($str)
    {
        return (int)$str;
    }

    /**
     * Säubert "böses" HTML raus
     * Zum Beispiel für Wiki
     *
     * @param string $str
     *
     * @return string
     */
    public static function cleanHTML($str)
    {
        $BBCode = new BBCode();

        $str = $BBCode->parseToBBCode($str);
        $str = $BBCode->parseToHTML($str);

        return $str;
    }

    /**
     * Prüft Datumsteile nach Korrektheit
     * Bei Korrektheit kommt $val wieder zurück ansonsten 0
     *
     * @param integer $val
     * @param string $type - DAY | MONTH | YEAR
     *
     * @return integer
     */
    public static function date($val, $type = 'DAY')
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
     * Prüft ein Datum auf Korrektheit
     *
     * @param integer $day
     * @param integer $month
     * @param integer $year
     *
     * @return boolean
     */
    public static function checkdate($day, $month, $year)
    {
        if (!\is_int($day)) {
            return false;
        }

        if (!\is_int($month)) {
            return false;
        }

        if (!\is_int($year)) {
            return false;
        }

        return \checkdate($month, $day, $year);
    }

    /**
     * use \QUI\Utils\StringHelper::removeLineBreaks
     *
     * @see        \QUI\Utils\StringHelper::removeLineBreaks
     * @deprecated use \QUI\Utils\StringHelper::removeLineBreaks
     *
     * @param string $text
     *
     * @return string
     */
    public static function removeLineBreaks($text)
    {
        return StringHelper::removeLineBreaks($text, '');
    }

    /**
     * Prüft eine Mail Adresse auf Syntax
     *
     * @param string $email - Mail Adresse
     *
     * @return boolean
     */
    public static function checkMailSyntax($email)
    {
        return \preg_match(
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
    public static function checkMySqlDatetimeSyntax($date)
    {
        // Nur Zahlen erlaubt
        if (\preg_match('/[^0-9- :]/i', $date)) {
            return false;
        }

        // Syntaxprüfung
        if (!\preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $date)) {
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
    public static function checkMySqlTimestampSyntax($date)
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
    public static function checkMySqlDateSyntax($date)
    {
        // Nur Zahlen erlaubt
        if (\preg_match('/[^0-9- :]/i', $date)) {
            return false;
        }

        // Syntaxprüfung
        if (!\preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
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
    public static function getPassword($length = 10)
    {
        if (!\is_int($length)) {
            $length = 10;
        }

        $newPass = "";
        $string  = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789()[]{}?!$%&/=*+~,.;:<>-_";

        \mt_srand((double)\microtime() * 1000000);

        for ($i = 1; $i <= $length; $i++) {
            $newPass .= \substr($string, \mt_rand(0, \strlen($string) - 1), 1);
        }

        return $newPass;
    }

    /**
     * Prüft ob die Mail Adresse eine Spam Wegwerf Mail Adresse ist
     *
     * @param string $mail - E-Mail Adresse
     *
     * @return boolean
     */
    public static function isSpamMail($mail)
    {
        $split = \explode('@', $mail);

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
            if (\strpos($split[1], $entry) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Löscht alle Zeichen aus einem Request welches für ein XSS verwendet werden könnte
     *
     * @param string $value
     *
     * @return string
     */
    public static function clearFormRequest($value)
    {
        if (\is_array($value)) {
            foreach ($value as $key => $entry) {
                $value[$key] = self::clearFormRequest($entry); // htmlspecialchars_decode($entry);
            }
        } else {
            if (!\is_string($value)) {
                return '';
            }

            $value = \htmlspecialchars_decode($value);
        }
        // alle zeichen undd HEX codes werden mit leer ersetzt
        $value = \str_replace(
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
    public static function urlEncodeString($str, $replace = "-")
    {
        if (!\is_string($replace)) {
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
        $str = \str_replace($reservedChars, $replace, $str);

        // filter non-letters and non-numbers and non-allowed url characters
        $str = \preg_replace('#[^\p{L}\d\-_.~]+#iu', $replace, $str);

        // trim outer and double replacement characters
        $str = \trim($str, $replace);

        // reduce multiple replacement chars in a row
        $str = \preg_replace('#\\'.$replace.'{2,}#i', $replace, $str);

        return StringHelper::toLower($str);
    }

    /**
     * Escape HTML
     *
     * @param string $str
     * @return string
     */
    public static function escapeHTML($str)
    {
        $str = \htmlspecialchars($str);

        return $str;
    }
}
