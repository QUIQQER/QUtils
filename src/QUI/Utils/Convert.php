<?php

/**
 * This file contains \QUI\Utils\Convert
 */

namespace QUI\Utils;

/**
 * Convert class, helper for converting different values
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qutils
 */

class Convert
{
    /**
     * Format a price
     *
     * 1.000,00 - 1000,00
     *
     * @param Integer $price
     * @param Integer $type -
     *                      1=round($betrag, 2)
     *                      2=$price value with , as decimal separator
     *                      3=$price value with . as decimal separator
     *
     * @return string
     */
    static function formPrice($price, $type = 1)
    {
        switch ($type) {
            case 2:
                $price = number_format(round($price, 2), '2', ',', '.');
                break;

            case 3:
                $price = number_format(round($price, 2), '2', '.', ',');
                break;

            default:
                $price = round($price, 2);
                break;
        }

        return $price;
    }

    /**
     * Format a byte number in human readable format
     *
     * @param integer $bytes
     *
     * @return string
     */
    static function formatBytes($bytes)
    {
        if (!$bytes) {
            return '0 B';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' '
               . $units[$power];
    }


    /**
     * Converts some Umlauts
     *
     * @param string $conv
     *
     * @return string
     */
    static function convertChars($conv)
    {
        $conv = str_replace("Ä", chr(196), $conv);
        $conv = str_replace("ä", chr(228), $conv);
        $conv = str_replace("Ö", chr(214), $conv);
        $conv = str_replace("ö", chr(246), $conv);
        $conv = str_replace("Ü", chr(220), $conv);
        $conv = str_replace("ü", chr(252), $conv);
        $conv = str_replace("ß", chr(223), $conv);
        $conv = str_replace("'", chr(39), $conv);
        $conv = str_replace("´", chr(180), $conv);
        $conv = str_replace("`", chr(96), $conv);

        return $conv;
    }

    /**
     * Converts a MySQL DateTime format to a Unix timestamp
     *
     * @param string $str
     *
     * @return integer
     */
    static function convertMySqlDatetime($str)
    {
        list($date, $time) = explode(' ', $str);
        list($year, $month, $day) = explode('-', $date);
        list($hour, $minute, $second) = explode(':', $time);

        $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

        return $timestamp;
    }

    /**
     * Convert umlauts e.g. ä to ae, u in ue etc.
     * it used to url converting
     *
     * @param string $conv
     * @param integer $code 0=encode 1=decode, standard=0
     *
     * @return string
     */
    static function convertUrlChars($conv, $code = 0)
    {
        if ($code == 0) {
            $conv = str_replace("Ä", "Ae", $conv);
            $conv = str_replace("ä", "ae", $conv);
            $conv = str_replace("Ö", "Oe", $conv);
            $conv = str_replace("ö", "oe", $conv);
            $conv = str_replace("Ü", "Ue", $conv);
            $conv = str_replace("ü", "ue", $conv);
            $conv = str_replace("ß", "sz", $conv);

            return $conv;
        }

        $conv = str_replace("Ae", "Ä", $conv);
        $conv = str_replace("ae", "ä", $conv);
        $conv = str_replace("Oe", "Ö", $conv);
        $conv = str_replace("oe", "ö", $conv);
        $conv = str_replace("Ue", "Ü", $conv);
        $conv = str_replace("ue", "ü", $conv);
        $conv = str_replace("sz", "ß", $conv);

        return $conv;
    }

    /**
     * Convert romanic signs to their latin counterpart
     *
     * @param string $str
     *
     * @return string
     */
    static function convertRoman($str)
    {
        $signs = array(
            'À' => 'A',
            'à' => 'a',
            'Á' => 'A',
            'á' => 'a',
            'Â' => 'A',
            'â' => 'a',
            'Ã' => 'A',
            'ã' => 'a',
            'Å' => 'A',
            'å' => 'a',
            'Æ' => 'AE',
            'æ' => 'ae',
            'Ā' => 'A',
            'ā' => 'a',
            'Ä' => 'AE',
            'ä' => 'ae',
            'Ç' => 'C',
            'ç' => 'c',
            'È' => 'E',
            'è' => 'e',
            'É' => 'E',
            'é' => 'e',
            'Ê' => 'E',
            'ê' => 'e',
            'Ë' => 'E',
            'ë' => 'e',
            'Ē' => 'E',
            'ē' => 'e',
            'Ì' => 'I',
            'ì' => 'i',
            'Í' => 'I',
            'í' => 'i',
            'Î' => 'I',
            'î' => 'i',
            'Ï' => 'I',
            'ï' => 'i',
            'Ī' => 'I',
            'ī' => 'i',
            'Ñ' => 'N',
            'ñ' => 'n',
            'Ò' => 'O',
            'ò' => 'o',
            'Ó' => 'O',
            'ó' => 'o',
            'Ô' => 'O',
            'ô' => 'o',
            'Õ' => 'O',
            'õ' => 'o',
            'Ø' => 'O',
            'ø' => 'o',
            'Ō' => 'O',
            'ō' => 'o',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Ö' => 'OE',
            'ö' => 'oe',
            'Ù' => 'U',
            'ù' => 'u',
            'Ú' => 'U',
            'ú' => 'u',
            'Û' => 'U',
            'û' => 'u',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ū' => 'U',
            'ū' => 'u',
            'ü' => 'ue',
            'Ü' => 'UE',
            'Ÿ' => 'Y',
            'ÿ' => 'y',
            'ß' => 'ss'
        );

        foreach ($signs as $from => $to) {
            $str = str_replace($from, $to, $str);
        }

        return $str;
    }
}
