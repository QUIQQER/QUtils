<?php

/**
 * This file contains QUI\Utils\Math
 */

namespace QUI\Utils;

/**
 * Commonly used mathematical functions
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qutils
 */

class Math
{
    /**
     * Percent calculation
     * Return the percentage integer value
     *
     * @param Integer|Float $amount
     * @param Integer|Float $total
     *
     * @return Integer
     *
     * @example $percent = QUI\Utils\Math::percent(20, 60); $percent=>33
     * @example echo QUI\Utils\Math::percent(50, 100) .'%';
     */
    static function percent($amount, $total)
    {
        if ($amount == 0 || $total == 0) {
            return 0;
        }

        return number_format(($amount * 100) / $total, 0);
    }

    /**
     * Resize each numbers in dependence
     *
     * @param Integer $var1 - number one
     * @param Integer $var2 - number two
     * @param Integer $max  - maximal number limit of each number
     *
     * @return array
     */
    static function resize($var1, $var2, $max)
    {
        if ($var1 > $max) {
            $resize_by_percent = ($max * 100) / $var1;

            $var2 = (int)round(($var2 * $resize_by_percent) / 100);
            $var1 = $max;
        }

        if ($var2 > $max) {
            $resize_by_percent = ($max * 100) / $var2;

            $var1 = (int)round(($var1 * $resize_by_percent) / 100);
            $var2 = $max;
        }

        return array(
            1 => $var1,
            2 => $var2
        );
    }
}
