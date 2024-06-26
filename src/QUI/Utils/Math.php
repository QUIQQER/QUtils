<?php

/**
 * This file contains QUI\Utils\Math
 */

namespace QUI\Utils;

use function ceil;
use function floatval;
use function number_format;
use function round;

/**
 * Commonly used mathematical functions
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class Math
{
    /**
     * Percent calculation
     * Return the percentage integer value
     *
     * @param float|integer $amount
     * @param float|integer $total
     * @param integer $decimals
     *
     * @return integer|float
     *
     * @example $percent = QUI\Utils\Math::percent(20, 60); $percent=>33
     * @example echo QUI\Utils\Math::percent(50, 100) .'%';
     */
    public static function percent(float|int $amount, float|int $total, int $decimals = 0): float|int
    {
        if ($amount == 0 || $total == 0) {
            return 0;
        }

        $result = number_format(($amount * 100) / $total, $decimals);

        return floatval($result);
    }

    /**
     * Resize each numbers in dependence
     *
     * @param integer $var1 - number one
     * @param integer $var2 - number two
     * @param integer $max - maximal number limit of each number
     *
     * @return array
     */
    public static function resize(int $var1, int $var2, int $max): array
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

        return [
            1 => $var1,
            2 => $var2
        ];
    }

    /**
     * Round to the multiple of x
     * 50 outputs 50, 52 outputs 55, 50.25 outputs 50
     *
     * found via http://stackoverflow.com/a/4133893
     *
     * @param float|integer $n - value to round
     * @param integer $x - Round step -> default=10
     * @return float|int
     */
    public static function roundUp(float|int $n, int $x = 10): float|int
    {
        return (round($n) % $x === 0) ? round($n) : round(($n + $x / 2) / $x) * $x;
    }

    /**
     * Ceil up to the multiple of x
     * 50 outputs 50, 52 outputs 55, 50.25 outputs 55
     *
     * found via http://stackoverflow.com/a/4133893
     *
     * @param float|integer $n - value to round
     * @param integer $x - Round step -> default=10
     * @return float|int
     */
    public static function ceilUp(float|int $n, int $x = 10): float|int
    {
        return (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
    }
}
